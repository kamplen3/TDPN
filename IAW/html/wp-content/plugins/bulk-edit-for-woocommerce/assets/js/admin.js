jQuery(document).ready(function ($) {
	//--------------------------------
	if (wp.media) {
		var pbeMedia = {
			setAttachment: function (attachment) {
				this.attachment = attachment;
			},
			addParamsURL: function (url, data) {
				if (!$.isEmptyObject(data)) {
					url += (url.indexOf("?") >= 0 ? "&" : "?") + $.param(data);
				}
				return url;
			},
			getThumb: function (attachment) {
				var control = this;
				if (typeof attachment !== "undefined") {
					this.attachment = attachment;
				}
				var t = new Date().getTime();
				if (typeof this.attachment.sizes !== "undefined") {
					if (typeof this.attachment.sizes.medium !== "undefined") {
						return control.addParamsURL(
							this.attachment.sizes.medium.url,
							{ t: t }
						);
					}
				}
				return control.addParamsURL(this.attachment.url, { t: t });
			},
			getURL: function (attachment) {
				if (typeof attachment !== "undefined") {
					this.attachment = attachment;
				}
				var t = new Date().getTime();
				return this.addParamsURL(this.attachment.url, { t: t });
			},
			getID: function (attachment) {
				if (typeof attachment !== "undefined") {
					this.attachment = attachment;
				}
				return this.attachment.id;
			},
			getInputID: function (attachment) {
				$(".attachment-id", this.preview).val();
			},
			setPreview: function ($el) {
				this.preview = $el;
			},
			insertFile: function (attachment) {
				if (typeof attachment !== "undefined") {
					this.attachment = attachment;
				}


				var url = this.attachment.url;
				var id = this.attachment.id;
				var mime = this.attachment.mime;
				$(".attachment-name", this.preview).val(attachment.title || attachment.filename);
				$(".attachment-url", this.preview).val(url);
				$(".attachment-mime", this.preview).val(mime);
				$(".attachment-id", this.preview)
					.val(id)
					.trigger("change");
				this.preview.addClass("attachment-added");
				this.showChangeBtn();
			},
			insertImage: function (attachment) {
				if (typeof attachment !== "undefined") {
					this.attachment = attachment;
				}

				var url = this.getURL();
				var id = this.getID();
				var mime = this.attachment.mime;
				$(".pbe-image-preview", this.preview)
					.addClass("pbe--has-file")
					.html('<img src="' + url + '" alt="">');
				$(".attachment-url", this.preview).val(this.toRelativeUrl(url));
				$(".attachment-mime", this.preview).val(mime);
				$(".attachment-id", this.preview)
					.val(id)
					.trigger("change");
				this.preview.addClass("attachment-added");
				this.showChangeBtn();
			},
			insertGallery: function (attachments) {
				$(".pbe-image-preview img", this.preview).remove();
				var attachment_ids = attachments.pluck("id");
				var that = this;
				console.log("Gallery_call_insert", attachments);
				var addBtn = $(".g-edit", this.preview);
				attachments.map(function (attachment) {
					attachment = attachment.toJSON();

					if (attachment.id) {
						that.attachment = attachment;
						var url = that.getURL();
						var id = that.getID();
						attachment_ids.push(id);
						// $(".pbe-image-preview", that.preview)
						// 	.addClass("pbe--has-file")
						// 	.append('<img src="' + url + '" alt="">');
						$('<img src="' + url + '" alt="">').insertBefore(
							addBtn
						);
					}
				});

				$(".attachment-id", this.preview)
					.val(attachment_ids.join(","))
					.trigger("change");
			},
			toRelativeUrl: function (url) {
				return url;
				//return url.replace( pbe_Control_Args.home_url, '' );
			},
			showChangeBtn: function () {
				$(".pbe--add", this.preview).addClass("pbe--hide");
				$(".pbe--change", this.preview).removeClass("pbe--hide");
				$(".pbe--remove", this.preview).removeClass("pbe--hide");
			},

			remove: function ($el) {
				if (typeof $el !== "undefined") {
					this.preview = $el;
				}
				$(".pbe-image-preview", this.preview)
					.removeAttr("style")
					.html("")
					.removeClass("pbe--has-file");
				$(".attachment-url", this.preview).val("");
				$(".attachment-mime", this.preview).val("");
				$(".attachment-id", this.preview)
					.val("")
					.trigger("change");
				this.preview.removeClass("attachment-added");

				$(".pbe--add", this.preview).removeClass("pbe--hide");
				$(".pbe--change", this.preview).addClass("pbe--hide");
				$(".pbe--remove", this.preview).addClass("pbe--hide");
			}
		};

		pbeMedia.controlMediaImage = wp.media({
			title: wp.media.view.l10n.addMedia,
			multiple: false,
			library: { type: "image" }
		});

		pbeMedia.controlMediaImage.on("select", function () {
			var attachment = pbeMedia.controlMediaImage
				.state()
				.get("selection")
				.first()
				.toJSON();
			pbeMedia.insertImage(attachment);
		});

		pbeMedia.controlMediaGallery = wp.media({
			//title: wp.media.view.l10n.addMedia,
			multiple: true,
			frame: "post",
			state: "gallery-edit",
			//editing:    true,
			// states: [
			// 	new wp.media.controller.Library({
			// 		filterable: 'all',
			// 		multiple: true
			// 	})
			// ],
			library: { type: "image" }
		});

		pbeMedia.controlMediaGallery.on("select", function () {
			var attachments = pbeMedia.controlMediaGallery
				.state()
				.get("selection");
			console.log("select", attachments);
			pbeMedia.insertGallery(attachments);
		});

		pbeMedia.controlMediaGallery.on("update", function () {
			var controller = pbeMedia.controlMediaGallery.states.get(
				"gallery-edit"
			);
			var attachments = controller.get("library");
			// Need to get all the attachment ids for gallery

			//var attachments = pbeMedia.controlMediaGallery.state().get("gallery-edit");
			console.log("update", attachments);
			pbeMedia.insertGallery(attachments);
		});

		$(document).on("click", ".pbe-edit-image", function (e) {
			e.preventDefault();
			var wrapper = $(this);
			pbeMedia.setPreview(wrapper);
			pbeMedia.controlMediaImage.open();
		});

		$(document).on("click", ".pbe-edit-gallery .g-edit", function (e) {
			e.preventDefault();
			var wrapper = $(this).closest(".pbe-edit-gallery");
			pbeMedia.setPreview(wrapper);
			pbeMedia.controlMediaGallery.open();
		});

		
				case "image":
					tpl_id = "tpl-pbe-image";
					break;

				case "number":
					if (fieldName === "regular_price") {
						data.set_price = true;
					}
					data.placeholder = PBE.edit_number_placeholder;
					break;
				case "date":
					data.placeholder = "YYYY-mm-dd HH:mm:ss";
					data.class = "input-date";
					break;
				case "tax":
					data.placeholder = "Choose category";
					data.class = "input-tax wide";
					tpl_id = "tpl-pbe-select-tax";
					data.name += "[]";
					break;

				default:
					data.placeholder = PBE.edit_string_placeholder;
			}

			if ("replace" == action && id_type == "string") {
				tpl_id = "tpl-pbe-replace-input";
				data.type = "text";
				$(".action-row.extra_editor").hide();
			} else {
				if (typeof settings.editor !== "undefined" && settings.editor) {
					tpl_id = "";
					$(".action-row.extra_editor").show();
				} else {
					$(".action-row.extra_editor").hide();
				}
			}

			var _fk = 'filed_id_type-' + settings._id + id_type;


			if (tpl_id && $("#" + tpl_id).length > 0) {
				html = $(template(data, tpl_id));
				html.addClass(_fk);
			}

			var fieldVal = $("#pbe-action-form .field-action-val");
			fieldVal.attr('data-type', id_type);
			fieldVal.attr('data-id', settings._id);

			fieldVal.html(html);

			if ("set_null" == action || "empty" == action) {
				fieldVal.hide();
			} else {
				fieldVal.show();
			}

			$(".input-date", fieldVal).datetimepicker({
				controlType: "select",
				oneLine: true,
				timeFormat: "HH:mm:ss",
				dateFormat: dateFormat,
				gotoCurrent: true,
				showButtonPanel: true,
				changeMonth: true,
				changeYear: true
			});

			find.select2Destroy(fieldVal);


			//Ajax search products.
			$(".select-products", fieldVal).select2({
				multiple: true,
				ajax: {
					url: PBE.ajax_url,
					dataType: "json",
					data: function (params) {
						return {
							q: params.term, // search term
							//page: params.page,
							action: "pbe_select_products",
							pbe_nonce: PBE.nonce
						};
					},
					cache: true
				},
				placeholder: "",
				minimumInputLength: 0
			});


			//Ajax search term.
			$(".input-tax", fieldVal).select2({
				multiple: true,
				dropdownAutoWidth: true,
				width: 'auto',
				ajax: {
					url: PBE.ajax_url,
					dataType: "json",
					data: function (params) {
						return {
							q: params.term, // search term
							//page: params.page,
							action: "pbe_search_term",
							tax: settings.source.taxonomy,
							pbe_nonce: PBE.nonce
						};
					},
					cache: true
				},
				placeholder: "",
				minimumInputLength: 0
			});

			$('input.input-text[name="edit_field_value"]').val(latsVal);

			

		}
	); // END action type changed

	


	$(document).on("change pbe_change", "#pbe-action-edit-field", function () {
		var select = $(this);
		var fieldName = select.val();
		var settings = false;
		try {
			settings = PBE.filter_fields[fieldName];
		} catch (e) {
			settings = false;
		}

		addEditFieldAction(fieldName, settings);

		if (settings.type === 'string' || 'meta_string' === settings.type) {
			$('.action-help-placeholders').show();
		} else {
			$('.action-help-placeholders').hide();
		}

		if (!settings.skip_variations) {
			$('#pbe-action-form .action-variation-condtions').show();
		} else {
			$('#pbe-action-form .action-variation-condtions').hide();
			$('input.pbe_skip_parent').removeAttr('checked');
		}

		

	});

	$("#pbe-action-form #pbe-action-edit-field")
		.select2({
			dropdownAutoWidth: true,
			width: 'auto',
		})
		.trigger("pbe_change");

	var variableFilter = new FindConditions({
		selector: "#pbe-variation-filters",
		groupName: "field_variations",
		fields: PBE.filter_variable_fields
	});

	variableFilter.init();

	$(".datetime-input").datetimepicker({
		controlType: "select",
		oneLine: true,
		timeFormat: "HH:mm:ss",
		dateFormat: dateFormat,
		gotoCurrent: true,
		showButtonPanel: true,
		changeMonth: true,
		changeYear: true
	});

	window.pbe_task_created = false;
	window.pbe_task_url = false;

	var do_task = function (task_id, modal) {
		console.log("do_task:", task_id);
		$.ajax({
			url: PBE.ajax_url,
			type: "post",
			data: {
				action: "pbe_do_task",
				task_id: task_id,
				pbe_nonce: PBE.nonce
			},
			cache: false,
			success: function (res) {
				console.log("do_task_result: ", res);
				$(".pbe-modal-heading").html(res.data.title);
				if (res.success) {
					window.pbe_task_created = true;
					window.pbe_task_url = res.data.url;
					$(".pbe-modal-confirm").attr("href", res.data.url);
					if (res.data.status === "next") {
						do_task(task_id, modal);
					} else {
						$(
							".pbe-bg-overlay, .pbe-task-confirm, .pbe-modal-close"
						).show();
						$(".pbe-modal-confirm").removeClass("disabled");
						$(".pbe-modal-check .circle-loader").addClass(
							"load-complete"
						);


					}
				} else {
					$(
						".pbe-bg-overlay, .pbe-task-confirm, .pbe-modal-close"
					).show();
					$(".pbe-modal-confirm").removeClass("disabled");
					$(".pbe-modal-check .circle-loader").addClass(
						"load-complete"
					);
				}
			}
		});
	};

	// When action form submit.
	$("#pbe-action-form").on("submit", function (e) {
		e.preventDefault();

		

		$(".pbe-modal-heading").html(PBE.creating_task_text);
		$(".pbe-bg-overlay, .pbe-task-confirm").show();
		var editor = tinymce.get("edit_field_editor");
		var editorContent = "";
		if (editor && !editor.isHidden()) {
			try {
				var editorContent = editor.getContent({ format: "raw" });
				$("#edit_field_editor").val(editorContent);
			} catch (e) { }
		} else {
			console.log("html");
		}

		var data = $(this).serialize();

		$.ajax({
			url: PBE.ajax_url,
			type: "post",
			data: data,
			cache: false,
			success: function (res) {
				console.log("task_created_result: ", res);
				$(".pbe-modal-heading").html(res.data.running_title);
				$(".pbe-modal-confirm").attr("href", res.data.url);
				if (res.success) {
					// window.pbe_task_created = true;
					window.pbe_task_url = res.data.url;
					do_task(res.data.task_id);
				} else {
					window.pbe_task_created = false;
					$(
						".pbe-bg-overlay, .pbe-task-confirm, .pbe-modal-close"
					).show();
					$(".pbe-modal-confirm").removeClass("disabled");
					$(".pbe-modal-check .circle-loader").addClass(
						"load-complete"
					);
				}
			}
		});
	});

	$(".schedule-editing-button").on("click", function (e) {
		e.preventDefault();
		$("#start-editing-button, .schedule-editing-button").hide();
		$(".action-schedule-extra").removeClass("pbe-hide");
		$("#edit-schedule-datetime").focus();
	});

	$(".schedule-cancel-button").on("click", function (e) {
		e.preventDefault();
		$("#start-editing-button, .schedule-editing-button").show();
		$(".action-schedule-extra").addClass("pbe-hide");
		$("#edit-schedule-datetime").val("");
	});

	$(".pbe-modal-confirm").on("click", function (e) {
		e.preventDefault();
		var button = $(this);
		if (!button.hasClass("disabled")) {
			// Redirect to task page.
			window.location = button.attr("href");
			return true;
		} else {
			return false;
		}
	});

	// When click to close the modal
	$(".pbe-modal-close").on("click", function (e) {
		e.preventDefault();
		$(".pbe-bg-overlay, .pbe-task-confirm, .pbe-modal-close").hide();
		$(".pbe-modal-heading").html(PBE.creating_task_text);
		$(".pbe-modal-confirm").attr("href", "#");
		$(".pbe-modal-check .circle-loader").removeClass("load-complete");
		$("#pbe-action-form").addClass("disabled");
		$("#pbe-action-edit-field option").removeAttr("selected");
		if (PBE.editing_field) {
			$(
				'#pbe-action-edit-field option[value="' +
				PBE.editing_field +
				'"]'
			).attr("selected", "selected");
		} else {
			$("#pbe-action-edit-field option")
				.eq(0)
				.attr("selected", "selected");
		}

		$("#pbe-action-edit-field").trigger("change");
		var form = $(".pbe-find-form").eq(0);
		var n = $('#pbed-preview-number-show').val();
		if (!n) {
			n = 20;
		}

		$('#pbe-find-posts_page_page').val(n);

		form.submit(); // Reload products to se the changes.
	});

	//----- END FOR BULK EDIT ACTION ----------------------------------------------------------------------------

	// When change the number item to show.
	$(document).on("change", "select#pbe-task-number-show", function (e) {
		var l = $(this).val();
		window.location = l;
	});

	var check_task_ids = [];
	$('.pbe-task-table .task-status[data-status="pending"]').each(function () {
		var item = $(this);
		var id = item.attr("data-id") || false;
		if (id) {
			check_task_ids.push(id);
		}
	});

	function check_tasks_status() {
		$.ajax({
			url: PBE.ajax_url,
			data: {
				action: "pbe_heart_beat",
				task_ids: check_task_ids.join(","),
				pbe_nonce: PBE.nonce
			},
			success: function (res) {
				if (res.success) {
					$.each(res.data, function (index, task) {
						var item = $(
							'.task-status[data-id="' + task.task_id + '"]'
						);
						item.attr("data-status", task.task_status);
						item.html(task.label);
					});
				}
			}
		});
	}

	//check_tasks_status();
	//setInterval( check_tasks_status, 2000 );

	// Cancel a task.
	$(".task-action.task-cancel").on("click", function (e) {
		e.preventDefault();
		var button = $(this);
		var id = $(this).attr("data-id");
		var row = $(this).closest("tr");
		if (!button.hasClass("loading")) {
			button.addClass("loading");
			$.ajax({
				url: PBE.ajax_url,
				data: {
					action: "pbe_task_cancel",
					task_id: id,
					pbe_nonce: PBE.nonce
				},
				success: function (res) {
					button.removeClass("loading");
					if (res.success) {
						var item = $('.task-status[data-id="' + id + '"]');
						item.attr("data-status", res.data.status);
						row.attr("data-status", res.data.status);
						item.html(res.data.label);
					}
				}
			});
		}
	});

	// Cancel a task.
	$(".task-action.task-continue").on("click", function (e) {
		e.preventDefault();
		var button = $(this);
		var id = $(this).attr("data-id");
		var row = $(this).closest("tr");
		if (!button.hasClass("loading")) {
			button.addClass("loading");
			$.ajax({
				url: PBE.ajax_url,
				data: {
					action: "pbe_task_continue",
					task_id: id,
					pbe_nonce: PBE.nonce
				},
				success: function (res) {
					button.removeClass("loading");
					if (res.success) {
						var item = $('.task-status[data-id="' + id + '"]');
						item.attr("data-status", res.data.status);
						row.attr("data-status", res.data.status);
						item.html(res.data.label);
					}
				}
			});
		}
	});

	// Cancel a task undo/revert.
	var do_revert = function (task_id, button) {
		$.ajax({
			url: PBE.ajax_url,
			data: {
				action: "pbe_task_revert",
				task_id: task_id,
				pbe_nonce: PBE.nonce
			},
			success: function (res) {
				if (button) {
					button.removeClass("loading");
				}
				if (res.success) {
					console.log("res", res);
					if (res.data.next_paged > 0) {
						do_revert(task_id);
					} else {
						var item = $('.task-status[data-id="' + task_id + '"]');
						item.attr("data-status", res.data.status);
						item.html(res.data.label);
						button.hide();
					}
				}
			}
		});
	};

	$(".task-action.task-undo").on("click", function (e) {
		e.preventDefault();
		var c = confirm(PBE.comfirm_revert);
		if (c) {
			var id = $(this).attr("data-id");
			var button = $(this);
			button.addClass("loading");
			do_revert(id, button);
		}
	});

	$(".task-action.task-del").on("click", function (e) {
		e.preventDefault();
		var c = confirm(PBE.comfirm_delete);
		if (c) {
			var id = $(this).attr("data-id");
			var button = $(this);
			var r = $(this).closest("tr");
			button.addClass("loading");
			$.ajax({
				url: PBE.ajax_url,
				data: {
					action: "pbe_task_del",
					task_id: id,
					pbe_nonce: PBE.nonce
				},
				success: function (res) {
					if (res.success) {
						if (r.length) {
							r.remove();
						} else {
							var url = $('.nav-tab-wrapper #tasks').attr('href') || '';
							if (url) {
								window.location = url;
							}
						}

					}
				}
			});
		}
	});

	// fetch('http://wcpro.local/wp-json/wc/v3/products?_locale=user', {
	// 	headers: {
	// 		'X-WP-Nonce' : PBE['X-WP-Nonce'],
	// 	}
	// })
	// 	.then(response => response.json())
	// 	.then(data => console.log(data));
});
