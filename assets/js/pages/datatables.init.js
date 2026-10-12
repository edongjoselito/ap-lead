$(document).ready(function () {
	var $table = $("#datatable");
	if ($table.length && $table.attr("data-dt-group") === "district") {
		$table.DataTable({
			pageLength: 20,
			lengthMenu: [[10, 20, 50, 100], [10, 20, 50, 100]],
			order: [[2, "asc"], [0, "asc"]],
			columnDefs: [{ orderable: false, targets: -1 }],
			drawCallback: function () {
				var api = this.api();
				$table.find("tr.district-group").remove();
				var lastGroup = null;
				api.rows({ page: "current" }).nodes().each(function (row) {
					var group = row.getAttribute("data-district") || "Other";
					if (group === lastGroup) {
						return;
					}
					var header = document.createElement("tr");
					header.className = "district-group";
					var cell = document.createElement("td");
					cell.colSpan = row.cells.length;
					cell.textContent = group;
					header.appendChild(cell);
					row.parentNode.insertBefore(header, row);
					lastGroup = group;
				});
			}
		});
	} else {
		$table.DataTable();
	}
	var a = $("#datatable-buttons").DataTable({ lengthChange: !1, buttons: ["copy", "excel", "pdf"] });
	$("#key-datatable").DataTable({ keys: !0 }),
	$("#selection-datatable").DataTable({ select: { style: "multi" } }),
	a.buttons().container().appendTo("#datatable-buttons_wrapper .col-md-6:eq(0)");
});
