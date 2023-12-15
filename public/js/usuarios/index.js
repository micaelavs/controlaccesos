$(document).ready(function () {
	
	$('.table').DataTable({
		language: {
            url: $endpoint_cdn+'/datatables/1.10.12/Spanish_sym.json',
			search: '_INPUT_',
			searchPlaceholder: 'Ingrese búsqueda'
		},
		responsive: true,
		info: true,
		bFilter: true,
		thousandSeparator: ".",
		buttons: [],
		order: [[0, 'asc']],
		columnDefs: [
		    {targets: 0, responsivePriority:1},
			{targets: 1, responsivePriority:1},
			{targets: 2, responsivePriority:2},
			{targets: 3, responsivePriority:3},
			{targets: 4, responsivePriority:4},
			{targets: 5, searchable: false, orderable: false,responsivePriority:5},
		]
	});
});
