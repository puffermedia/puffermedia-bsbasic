/**
 * Bootstrap Basic — sticky header behavior.
 * Only loaded when the "Sticky Header" param is enabled. Keeps in-page links
 * below the header and, per the Sticky Header Behavior param, shrinks the
 * header or hides it while scrolling down.
 */
(function () {
	"use strict";

	document.addEventListener("DOMContentLoaded", function () {
		var header = document.querySelector("header.bsbasic-header.sticky-top");

		if (!header) {
			return;
		}

		var root = document.documentElement;
		var mode = header.getAttribute("data-sticky") || "fixed";

		// scroll-padding-top (see _header.scss) reads this, so anchors stop
		// below the header instead of under it.
		var setOffset = function () {
			root.style.setProperty("--bsbasic-sticky-offset", (header.offsetHeight + 8) + "px");
		};

		setOffset();
		window.addEventListener("resize", setOffset);

		if (mode === "shrink") {
			var shrunk = false;

			var shrink = function () {
				// Two thresholds, so the height change cannot flip it back and forth.
				if (!shrunk && window.scrollY > 60) {
					shrunk = true;
				} else if (shrunk && window.scrollY < 10) {
					shrunk = false;
				}

				header.classList.toggle("is-scrolled", shrunk);
			};

			window.addEventListener("scroll", shrink, { passive: true });
			shrink();
		}

		if (mode === "autohide") {
			var lastY = window.scrollY;
			var ticking = false;

			var autohide = function () {
				var y = window.scrollY;
				var menuOpen = header.querySelector(".navbar-collapse.show, .offcanvas.show");

				if (y <= 0 || y < lastY - 4 || menuOpen) {
					header.classList.remove("is-hidden");
				} else if (y > lastY + 4 && y > header.offsetHeight) {
					header.classList.add("is-hidden");
				}

				lastY = y;
				ticking = false;
			};

			window.addEventListener("scroll", function () {
				if (!ticking) {
					ticking = true;
					window.requestAnimationFrame(autohide);
				}
			}, { passive: true });

			// Keyboard users tabbing into a hidden header bring it back.
			header.addEventListener("focusin", function () {
				header.classList.remove("is-hidden");
			});
		}
	});
})();
