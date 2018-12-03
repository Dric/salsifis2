/**
 * Created by cedric.gallard on 24/05/2017.
 */

if (isHomePage) {
	$.ajax({
		method  : "POST",
		url     : "index.php",
		dataType: "html",
		timeout : 2000,
		data    : {aSync: true, diskUsage: true}
	}).done(function (data) {
		$('#diskUsage').hide().html(data).fadeIn();
	});

	if (displayExternalIP) {
		$.ajax({
			method  : "POST",
			url     : "index.php",
			dataType: "html",
			timeout : 2000,
			data    : {aSync: true, externalIP: true}
		}).done(function (data) {
			$('#externalIP').hide().html(data).fadeIn();
		});
	}

	(function (global, factory) {
		typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory(require('uikit-util')) :
		typeof define === 'function' && define.amd ? define('uikittimer', ['uikit-util'], factory) :
		(global.UIkitCountdown = factory(global.UIkit.util));
	}(this, (function (uikitUtil) { 'use strict';
	
		var Class = {
	
			connected: function() {
				!uikitUtil.hasClass(this.$el, this.$name) && uikitUtil.addClass(this.$el, this.$name);
			}
	
		};
	
		var Component = {
	
			mixins: [Class],
	
			props: {
				date: String,
				clsWrapper: String
			},
	
			data: {
				date: '',
				clsWrapper: '.uk-countdown-%unit%'
			},
	
			computed: {
	
				date: function(ref) {
					var date = ref.date;
	
					return Date.parse(date);
				},
	
				days: function(ref, $el) {
					var clsWrapper = ref.clsWrapper;
	
					return uikitUtil.$(clsWrapper.replace('%unit%', 'days'), $el);
				},
	
				hours: function(ref, $el) {
					var clsWrapper = ref.clsWrapper;
	
					return uikitUtil.$(clsWrapper.replace('%unit%', 'hours'), $el);
				},
	
				minutes: function(ref, $el) {
					var clsWrapper = ref.clsWrapper;
	
					return uikitUtil.$(clsWrapper.replace('%unit%', 'minutes'), $el);
				},
	
				seconds: function(ref, $el) {
					var clsWrapper = ref.clsWrapper;
	
					return uikitUtil.$(clsWrapper.replace('%unit%', 'seconds'), $el);
				},
	
				units: function() {
					var this$1 = this;
	
					return ['days', 'hours', 'minutes', 'seconds'].filter(function (unit) { return this$1[unit]; });
				}
	
			},
	
			connected: function() {
				this.start();
			},
	
			disconnected: function() {
				var this$1 = this;
	
				this.stop();
				this.units.forEach(function (unit) { return uikitUtil.empty(this$1[unit]); });
			},
	
			events: [
	
				{
	
					name: 'visibilitychange',
	
					el: document,
	
					handler: function() {
						if (document.hidden) {
							this.stop();
						} else {
							this.start();
						}
					}
	
				}
	
			],
	
			update: {
	
				write: function() {
					var this$1 = this;
	
	
					var timespan = getTimeSpan(this.date);
	
					if (timespan.total <= 0) {
	
						this.stop();
	
						timespan.days
							= timespan.hours
							= timespan.minutes
							= timespan.seconds
							= 0;
					}
	
					this.units.forEach(function (unit) {
	
						var digits = String(Math.floor(timespan[unit]));
	
						digits = digits.length < 2 ? ("0" + digits) : digits;
	
						var el = this$1[unit];
						if (el.textContent !== digits) {
							digits = digits.split('');
	
							if (digits.length !== el.children.length) {
								uikitUtil.html(el, digits.map(function () { return '<span></span>'; }).join(''));
							}
	
							digits.forEach(function (digit, i) { return el.children[i].textContent = digit; });
						}
	
					});
	
				}
	
			},
	
			methods: {
	
				start: function() {
					var this$1 = this;
	
	
					this.stop();
	
					if (this.date && this.units.length) {
						this.$emit();
						this.timer = setInterval(function () { return this$1.$emit(); }, 1000);
					}
	
				},
	
				stop: function() {
	
					if (this.timer) {
						clearInterval(this.timer);
						this.timer = null;
					}
	
				}
	
			}
	
		};
	
		function getTimeSpan(date) {
	
			var total = Date.now() - date;
	
			return {
				total: total,
				seconds: total / 1000 % 60,
				minutes: total / 1000 / 60 % 60,
				hours: total / 1000 / 60 / 60 % 24,
				days: total / 1000 / 60 / 60 / 24
			};
		}
	
		/* global UIkit, 'timer' */
	
		if (typeof window !== 'undefined' && window.UIkit) {
			window.UIkit.component('timer', Component);
		}
	
		return Component;
	
	})));
	

	$('.serverSettingsLink').on('click', function(e){
		e.preventDefault();
		var id = $(this).data('id');
		$.ajax({
			method  : "POST",
			url     : "index.php",
			dataType: "html",
			timeout : 2000,
			data    : {aSync: true, serverSettings: true}
		}).done(function (data) {
			$('#serverSettings').html(data);
			UIkit.modal('#serverSettings').show();
		});
	});

}

$('.serverVersionLink').on('click', function(e){
	e.preventDefault();
	var id = $(this).data('id');
	$.ajax({
		method  : "POST",
		url     : "index.php",
		dataType: "html",
		timeout : 5000,
		data    : {aSync: true, serverVersion: true}
	}).done(function (data) {
		$('#serverVersion').html(data);
		UIkit.modal('#serverVersion').show();
	});
});

if ($.fn.DataTable) {

	var dataTablesLanguage = {
		processing:     "Traitement en cours...",
		search:         "Rechercher&nbsp;:",
		lengthMenu:    "Afficher _MENU_ &eacute;l&eacute;ments",
		info:           "Affichage de l'&eacute;lement _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
		infoEmpty:      "Affichage de l'&eacute;lement 0 &agrave; 0 sur 0 &eacute;l&eacute;ments",
		infoFiltered:   "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
		infoPostFix:    "",
		loadingRecords: "Chargement en cours...",
		zeroRecords:    "Aucun &eacute;l&eacute;ment &agrave; afficher",
		emptyTable:     "Aucune donnée disponible dans le tableau",
		paginate: {
			first:      "Premier",
			previous:   "Pr&eacute;c&eacute;dent",
			next:       "Suivant",
			last:       "Dernier"
		},
		aria: {
			sortAscending:  ": activer pour trier la colonne par ordre croissant",
			sortDescending: ": activer pour trier la colonne par ordre décroissant"
		}
	};

	$('#fileBrowser').DataTable({
		paging: false,
		language: dataTablesLanguage,
		autoWidth: false,
		"initComplete": function(settings, json) {
			$('#salsifis-table-container').fadeIn();
		}
	});

	var torrentTable = $('#torrentBrowser').DataTable({
		paging: false,
		language: dataTablesLanguage,
		autoWidth: false,
		rowId: 'id',
		"initComplete": function(settings, json) {
			var api = this.api();
			api.$('.torrent-status').click( function () {
				var search = (this.data('search'))? this.data('search') : this.innerHTML;
				api.search( search ).draw();
			});
			$('#salsifis-table-container').fadeIn();
		}
	});
	// Update table from html : https://stackoverflow.com/a/36530654/1749967
	// table.rows().invalidate().draw();

	$('.torrentDetailLink').on('click', function(e){
		e.preventDefault();
		var id = $(this).data('id');
		$.ajax({
			method  : "POST",
			url     : "index.php",
			dataType: "html",
			timeout : 2000,
			data    : {aSync: true, downloads: 'torrentDetail', torrentId: id}
		}).done(function (data) {
			$('#torrentDetail').html(data);
			UIkit.modal('#torrentDetail').show();
		});
	});

	$('.changeTrackerLink').on('click', function(e){
		e.preventDefault();
		var id = $(this).data('id');
		$.ajax({
			method  : "POST",
			url     : "index.php",
			dataType: "html",
			timeout : 2000,
			data    : {aSync: true, downloads: 'trackersList'}
		}).done(function (data) {
			$('#changeTracker').html(data);
			UIkit.modal('#changeTracker').show();
		});
	});
	//torrentTable.order([2, 'asc']).draw();

	/*$(function() {
		function refreshTorrents(){
			var order = torrentTable.order();
			$('#torrentsTableBody').load('index.php?page=downloads&aSync&downloads=getTorrentsTableRows&order=' + order);

				torrentTable = torrentTable.rows().invalidate().draw();
				//torrentTable.order(order).draw();
				UIkit.update(event = 'update');
		}
		setInterval(refreshTorrents, 10000 );
	});*/



}

