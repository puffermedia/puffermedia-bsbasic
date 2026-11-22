/**
 * Bootstrap Basic — collapsible sidebar menu.
 * Only loaded when the "Sidebar Menu" param is set to Collapsible. Without
 * this script the submenus simply stay expanded: the CSS collapses them only
 * under .is-ready, which is added here.
 */
(function () {
	"use strict";

	document.addEventListener("DOMContentLoaded", function () {
		var sidebars = document.querySelectorAll(".bsbasic-sidebar--collapsible");

		Array.prototype.forEach.call(sidebars, function (sidebar) {
			var setOpen = function (item, open) {
				var button = item.querySelector(":scope > .mod-menu__toggle-sub");

				item.classList.toggle("is-open", open);

				if (button) {
					button.setAttribute("aria-expanded", open ? "true" : "false");
				}
			};

			// The branch holding the current page starts open. Test for the
			// current item, not "active": core also marks a menu alias to the
			// current page "active", which would open an unrelated branch.
			Array.prototype.forEach.call(sidebar.querySelectorAll(".mod-menu li.parent"), function (item) {
				setOpen(item, item.classList.contains("current") || !!item.querySelector("li.current"));
			});

			sidebar.addEventListener("click", function (event) {
				var button = event.target.closest(".mod-menu__toggle-sub");

				if (!button || !sidebar.contains(button)) {
					return;
				}

				var item = button.closest("li");

				if (item) {
					setOpen(item, !item.classList.contains("is-open"));
				}
			});

			sidebar.classList.add("is-ready");
		});
	});
})();
