/**
 * Bootstrap Basic — scroll-in entrance animations.
 * Only loaded when the "Entrance Animation" param is not None. index.php puts
 * the settings on <html> (data-bsbasic-anim, -targets, -stagger); the styles
 * live in scss/_animations.scss. Skipped for visitors who prefer reduced motion.
 */
(function () {
	"use strict";

	var root = document.documentElement;
	var effect = root.getAttribute("data-bsbasic-anim");

	// index.php sets .bsbasic-anim-pending so the targets are hidden from the
	// first paint; it must come off on every path out of this script.
	var release = function () {
		root.classList.remove("bsbasic-anim-pending");
	};

	if (!effect || !("IntersectionObserver" in window)
		|| window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
		release();
		return;
	}

	// Targets carry .bsbasic-reveal (section bands add --section); see index.php,
	// chromes/card.php and mod_feed/tiles.php. Core mod_articles items are
	// core markup we do not override, so they stay on a selector, as do the
	// com_content blog items and Bootstrap cards written into an article body.
	var SECTIONS = ".bsbasic-reveal--section";
	var CARDS = ".bsbasic-reveal:not(.bsbasic-reveal--section), .mod-articles-item, "
		+ ".blog-items > .blog-item, .com-content-article__body .card";

	// Pause between cards that appear together (ms, set from the Animation
	// Speed param; 80 by default), and the cap on the number of steps.
	var STEP = (parseInt(root.getAttribute("data-bsbasic-anim-step"), 10) || 80) / 1000;
	var MAX_STEPS = 8;

	document.addEventListener("DOMContentLoaded", function () {
		var which = root.getAttribute("data-bsbasic-anim-targets") || "both";
		var stagger = root.getAttribute("data-bsbasic-anim-stagger") === "1";
		var list = [];

		if (which !== "cards") {
			Array.prototype.forEach.call(document.querySelectorAll(SECTIONS), function (section) {
				// With cards on too, a band that holds cards leaves the
				// animating to them (top-a, bottom); bare bands animate as a block.
				if (which === "sections" || !section.querySelector(CARDS)) {
					list.push(section);
				}
			});
		}

		if (which !== "sections") {
			Array.prototype.forEach.call(document.querySelectorAll(CARDS), function (card) {
				// Of nested cards only the innermost animate, so a module
				// card's tiles stagger inside it.
				if (!card.querySelector(CARDS)) {
					list.push(card);
				}
			});
		}

		if (!list.length) {
			release();
			return;
		}

		var reveal = function (el) {
			el.classList.add("is-visible");

			var done = function () {
				el.classList.remove("bsbasic-anim-item", "bsbasic-anim--" + effect, "is-visible");
				el.style.removeProperty("--bsbasic-anim-delay");
				el.removeEventListener("transitionend", onEnd);
			};
			var onEnd = function (event) {
				if (event.target === el && event.propertyName === "opacity") {
					done();
				}
			};

			el.addEventListener("transitionend", onEnd);
			// Fallback if the transition never fires (element hidden, tab in background).
			window.setTimeout(done, 3000);
		};

		var observer = new IntersectionObserver(function (entries) {
			var shown = entries.filter(function (entry) {
				return entry.isIntersecting;
			});

			// Reading order: top to bottom, then left to right.
			shown.sort(function (a, b) {
				return (a.boundingClientRect.top - b.boundingClientRect.top)
					|| (a.boundingClientRect.left - b.boundingClientRect.left);
			});

			shown.forEach(function (entry, index) {
				observer.unobserve(entry.target);

				if (stagger && shown.length > 1) {
					entry.target.style.setProperty(
						"--bsbasic-anim-delay",
						(Math.min(index, MAX_STEPS) * STEP) + "s"
					);
				}

				reveal(entry.target);
			});
		}, { rootMargin: "0px 0px -10% 0px" });

		list.forEach(function (el) {
			el.classList.add("bsbasic-anim-item", "bsbasic-anim--" + effect);
		});

		// The hidden state takes over from the pending class in the same frame.
		release();

		// Commit the hidden state to a frame before watching. Without it, targets
		// already in view on load have nothing to transition from and just appear.
		void root.offsetHeight;
		window.requestAnimationFrame(function () {
			window.requestAnimationFrame(function () {
				list.forEach(function (el) {
					observer.observe(el);
				});
			});
		});
	});
})();
