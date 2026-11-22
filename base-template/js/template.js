/**
 * Bootstrap Basic — back-to-top button behavior.
 * Only loaded when the "Back-to-Top Button" param is enabled.
 */
(function () {
	"use strict";

	document.addEventListener("DOMContentLoaded", function () {
		var btn = document.getElementById("bsbasicBackToTop");

		if (!btn) {
			return;
		}

		var threshold = parseInt(btn.getAttribute("data-threshold"), 10);

		if (isNaN(threshold)) {
			threshold = 300;
		}

		var toggle = function () {
			if (window.scrollY > threshold) {
				btn.classList.add("show");
			} else {
				btn.classList.remove("show");
			}
		};

		window.addEventListener("scroll", toggle, { passive: true });
		toggle();

		btn.addEventListener("click", function (event) {
			event.preventDefault();
			window.scrollTo({ top: 0, behavior: "smooth" });
		});
	});
})();
