(function ($) {
    $.entwine("ss", function ($) {
        // HasOneSelector

        $(".ss-gridfield-hasOneSelector-reset_button[data-relation]").entwine({
            onclick: function () {
                this.parents(".ss-gridfield:first")
                    .find('td[data-relation="' + this.data("relation") + '"] ')
                    .each(function () {
                        $(this).find("input").prop("checked", false);
                    });

                return false;
            },
        });

        $(".ss-gridfield-hasOneSelector-holder").entwine({
            onclick: function (ev) {
                ev.preventDefault();
                ev.stopPropagation();

                var input = this.find("input");
                var checked = input.prop("checked");
                input.prop("checked", !checked);

                this.parents(".ss-gridfield:first")
                    .find('td[data-relation="' + input.data("relation") + '"] ')
                    .each(function () {
                        $(this).find("input").prop("checked", !checked);
                    });
            },
        });

        // AddNewInlineExtended

        $(".ss-gridfield.ss-gridfield-add-inline-extended--table").entwine({
            reload: function (opts, success) {
                var $grid = this,
                    $added = $grid.find("tbody:first").find(".ss-gridfield-inline-new-extended--row").detach(),
                    args = arguments;

                this._super(opts, function () {
                    if ($added.length) {
                        if ($grid.data("prepend")) {
                            $added.prependTo($grid.find("tbody:first"));
                        } else {
                            $added.appendTo($grid.find("tbody:first"));
                        }

                        $grid.find("tbody:first").children(".ss-gridfield-no-items:first").hide();
                    }

                    if (success) {
                        success.apply($grid, args);
                    }
                });
            },
            onaddnewinlinextended: function (e, $trigger) {
                if (e.target != this[0]) {
                    return;
                }

                var $grid = this,
                    $tbody = $grid.find("tbody:first"),
                    num = $grid.data("add-inline-num") || 1;

                if ($trigger && $trigger.data("ajax")) {
                    var isConstructive = $trigger.hasClass("btn-primary"),
                        $classSelector = $trigger
                            .siblings(".ss-gridfield-inline-new-extended--class-selector:first")
                            .find(":input"),
                        data = {
                            _datanum: num,
                        };

                    if ($classSelector.length) {
                        if (!$classSelector.val()) {
                            alert("Please select a type to create");
                            return;
                        }

                        data[$grid.attr("id") + "_modelClass"] = $classSelector.val();
                    }

                    $trigger.addClass("disabled");

                    $.ajax({
                        url: $trigger[0].href,
                        dataType: "html",
                        data: data,
                        success: function (data) {
                            $trigger.removeClass("disabled");

                            var $data = $(data);
                            $data.find("ss-gridfield-editable-row--toggle");

                            if ($grid.data("prepend")) {
                                $tbody.prepend($data);
                            } else {
                                $tbody.append($data);
                            }

                            $tbody.children(".ss-gridfield-no-items:first").hide();
                            $data.find("input:first").focus();
                        },
                        error: function (e) {
                            alert(ss.i18n._t("GRIDFIELD.ERRORINTRANSACTION"));
                            $trigger.removeClass("disabled");
                        },
                    });
                } else {
                    var tmpl = window.tmpl,
                        row = this.find(".ss-gridfield-add-inline-extended--template:last");

                    tmpl.cache[this[0].id + "-ss-gridfield-add-inline-extended--template"] = tmpl(row.html());

                    var item = $(
                        tmpl(this[0].id + "-ss-gridfield-add-inline-extended--template", {
                            num: num,
                        }),
                    );

                    $tbody.append(item);
                    $tbody.children(".ss-gridfield-no-items:first").hide();

                    item.find("input:first").focus();
                }

                this.data("add-inline-num", num + 1);
            },
        });

        $(".ss-gridfield-add-new-inline-extended--button").entwine({
            onclick: function () {
                this.getGridField().trigger("addnewinlinextended", [this]);
                return false;
            },
        });

        $(".ss-gridfield-inline-new-extended--row-delete").entwine({
            onclick: function () {
                var $grid = this.parents(".ss-gridfield:first");

                if (confirm(ss.i18n._t("GridFieldExtensions.CONFIRMDEL", "Are you sure you want to delete this?"))) {
                    this.parents("tbody:first")
                        .find(
                            "tr[data-inline-new-extended-row=" +
                                this.parents("tr[data-inline-new-extended-row]:first").data("inlineNewExtendedRow") +
                                "]",
                        )
                        .remove();

                    $grid.showNoItemsMessage();
                }

                return false;
            },
        });

        $(".ss-gridfield-add-inline-extended--toggle").entwine({
            onclick: function () {
                var $parent = this.parents("tr:first");

                this.toggleClass("ss-gridfield-add-inline-extended--toggle_open");

                $parent
                    .parent()
                    .find(
                        ".ss-gridfield-inline-new-extended--row--has-columns[data-inline-new-extended-row=" +
                            $parent.data("inlineNewExtendedRow") +
                            "]",
                    )
                    .toggleClass("ss-gridfield-inline-new-extended--row--has-columns_open");

                return false;
            },
        });

        // EditableRow

        $(".cms-container").entwine({
            OpenGridFieldToggles: {},
            saveTabState: function () {
                var $that = this,
                    OpenGridFieldToggles = $that.getOpenGridFieldToggles();

                $that._super();

                $that.find(".ss-gridfield.ss-gridfield-editable-rows").each(function () {
                    var $this = $(this),
                        openToggles = $this.getOpenToggles();

                    if (openToggles.length) {
                        OpenGridFieldToggles[$this.attr("id")] = $this.getOpenToggles();
                    }
                });
            },
            restoreTabState: function (overrideStates) {
                var $that = this,
                    OpenGridFieldToggles = $that.getOpenGridFieldToggles();

                $that._super(overrideStates);

                $.each(OpenGridFieldToggles, function (id, openToggles) {
                    $that.find("#" + id + ".ss-gridfield.ss-gridfield-editable-rows").reopenToggles(openToggles);
                });

                $that.find(".ss-gridfield-editable-row--toggle_start").click();

                $that.setOpenGridFieldToggles({});
            },
        });

        $(".ss-gridfield.ss-gridfield-editable-rows").entwine({
            reload: function (opts, success) {
                var $grid = this,
                    openToggles = $grid.getOpenToggles(),
                    args = arguments;

                this._super(opts, function () {
                    $grid.reopenToggles(openToggles);
                    $grid.find(".ss-gridfield-editable-row--toggle_start").click();

                    if (success) {
                        success.apply($grid, args);
                    }
                });
            },
            getOpenToggles: function () {
                var $grid = this,
                    openToggles = [];

                if ($grid.hasClass("ss-gridfield-editable-rows_disableToggleState")) {
                    return openToggles;
                }

                $grid.find(".ss-gridfield-editable-row--toggle_open").each(function (key) {
                    var $this = $(this),
                        $holder = $this.parents("td:first"),
                        $parent = $this.parents("tr:first"),
                        $currentGrid = $parent.parents(".ss-gridfield:first"),
                        $editable = $parent.next();

                    if (
                        !$editable.hasClass("ss-gridfield-editable-row--row") ||
                        $editable.data("id") != $parent.data("id") ||
                        $editable.data("class") != $parent.data("class")
                    ) {
                        $editable = null;
                    } else if ($currentGrid.hasClass("ss-gridfield-editable-rows_disableToggleState")) {
                        return true;
                    }

                    openToggles[key] = {
                        link: $holder.data("link"),
                    };

                    if ($editable) {
                        $editable.find(".ss-tabset.ui-tabs").each(function () {
                            if (!openToggles[key].tabs) {
                                openToggles[key].tabs = {};
                            }

                            openToggles[key].tabs[this.id] = $(this).tabs("option", "selected");
                        });

                        if ($currentGrid.hasClass("ss-gridfield-editable-rows_allowCachedToggles")) {
                            openToggles[key].row = $editable.detach();
                        }
                    }
                });

                return openToggles;
            },
            reopenToggles: function (openToggles) {
                var $grid = this,
                    openTabsInToggle = function (currentToggle, $row) {
                        if (currentToggle.hasOwnProperty("tabs") && currentToggle.tabs) {
                            $.each(currentToggle.tabs, function (key, value) {
                                $row.find("#" + key + ".ss-tabset.ui-tabs").tabs({
                                    active: value,
                                });
                            });
                        }
                    };

                if ($grid.hasClass("ss-gridfield-editable-rows_disableToggleState")) {
                    return;
                }

                $.each(openToggles, function (key) {
                    if (openToggles[key].hasOwnProperty("link") && openToggles[key].link) {
                        var $toggleHolder = $grid.find(
                            "td.ss-gridfield-editable-row--icon-holder[data-link='" + openToggles[key].link + "']",
                        );

                        if (!$toggleHolder.length) {
                            return true;
                        }
                    } else {
                        return true;
                    }

                    if (openToggles[key].hasOwnProperty("row") && openToggles[key].row) {
                        var $parent = $toggleHolder.parents("tr:first");

                        $toggleHolder
                            .find(".ss-gridfield-editable-row--toggle")
                            .addClass(
                                "ss-gridfield-editable-row--toggle_loaded ss-gridfield-editable-row--toggle_open",
                            );

                        if (!$parent.next().hasClass("ss-gridfield-editable-row--row")) {
                            $parent.after(openToggles[key].row);
                        }
                    } else if (openToggles[key].hasOwnProperty("link") && openToggles[key].link) {
                        $toggleHolder.find(".ss-gridfield-editable-row--toggle").trigger(
                            "click",
                            function ($newRow) {
                                $grid.find(".ss-gridfield.ss-gridfield-editable-rows").reopenToggles(openToggles);
                                openTabsInToggle(openToggles[key], $newRow);
                            },
                            false,
                        );
                    }
                });
            },
        });

        $(".ss-gridfield-editable-row--toggle").entwine({
            onclick: function (e, callback, noFocus) {
                var $this = this,
                    $holder = $this.parents("td:first"),
                    link = $holder.data("link"),
                    $parent = $this.parents("tr:first");

                $this.removeClass("ss-gridfield-editable-row--toggle_start");

                if ($parent.hasClass("ss-gridfield-editable-row--loading")) {
                    return false;
                }

                if (link && !$this.hasClass("ss-gridfield-editable-row--toggle_loaded")) {
                    $parent.addClass("ss-gridfield-editable-row--loading");

                    $.ajax({
                        url: link,
                        dataType: "html",
                        success: function (data) {
                            var $data = $(data);
                            $this.addClass(
                                "ss-gridfield-editable-row--toggle_loaded ss-gridfield-editable-row--toggle_open",
                            );
                            $parent
                                .addClass("ss-gridfield-editable-row--reference")
                                .removeClass("ss-gridfield-editable-row--loading");
                            $parent.after($data);

                            $data.find(".ss-gridfield-editable-row--toggle_start").click();

                            if (noFocus !== false) {
                                $data.find("input:first").focus();
                            }

                            if (typeof callback === "function") {
                                callback($data, $this, $parent);
                            }
                        },
                        error: function (e) {
                            alert(ss.i18n._t("GRIDFIELD.ERRORINTRANSACTION"));
                            $parent.removeClass("ss-gridfield-editable-row--loading");
                        },
                    });
                } else if (link) {
                    var $editable = $parent.next();

                    if (
                        $editable.hasClass("ss-gridfield-editable-row--row") &&
                        $editable.data("id") == $parent.data("id") &&
                        $editable.data("class") == $parent.data("class")
                    ) {
                        $this.toggleClass("ss-gridfield-editable-row--toggle_open");

                        if ($this.hasClass("ss-gridfield-editable-row--toggle_open")) {
                            $editable.removeClass("ss-gridfield-editable-row--row_hide").find("input:first").focus();
                        } else {
                            $editable.addClass("ss-gridfield-editable-row--row_hide");
                        }
                    }
                }

                return false;
            },
        });

        // AddExistingPicker

        $(".add-existing-picker-actions--add-items").entwine({
            onclick: function (e, callback) {
                var $dialog = this.getDialog();

                this.addCloseDialogIfRequired($dialog);
                this.addItems(null, null, "", function () {
                    if (callback) {
                        callback();
                    }
                    $dialog.data("grid").reload();
                    $dialog.dialog("close");
                });
                e.preventDefault();
            },
            getDialog: function () {
                return this.closest(".add-existing-search-dialog").children(".ui-dialog-content:first");
            },
            addItems: function ($dialog, itemsToAdd, link, callback, async) {
                var that = this;

                if (!$dialog) {
                    $dialog = that.getDialog();
                }

                if (!itemsToAdd) {
                    itemsToAdd = $dialog.data("items-to-add") || [];
                }

                if (!itemsToAdd.length) {
                    statusMessage(ss.i18n._t("GridField_AddExistingPicker.NO_ITEMS", "No items selected"), "error");

                    if (!$dialog.find(".add-existing-search-items").children().length) {
                        $dialog.dialog("close");
                    }
                }

                if (!link) {
                    link = $dialog.find(".add-existing-picker-items").data("add-link");
                }

                if (!callback) {
                    callback = function () {
                        if ($dialog.data("grid")) {
                            $dialog.data("grid").reload();
                        }
                    };
                }

                that.clearItemsToAdd($dialog);
                if (!async) {
                    $dialog.parent().addClass("loading loading_hiddenContents");
                }

                $.post(link, { ids: itemsToAdd }, callback).always(function () {
                    $dialog.parent().removeClass("loading loading_hiddenContents");
                });
            },
            undoItems: function (items, $dialog, link, callback, async) {
                var that = this;

                if (!$dialog) {
                    $dialog = that.getDialog();
                }

                if (!items.length) {
                    return;
                }

                if (!link) {
                    link = $dialog.find(".add-existing-picker-items").data("undo-link");
                }

                if (!callback) {
                    callback = function () {
                        if ($dialog.data("grid")) {
                            $dialog.data("grid").reload();
                        }
                    };
                }

                if (!async) {
                    $dialog.parent().addClass("loading loading_hiddenContents");
                }

                $.post(link, { ids: items }, function () {
                    var args = [].slice.call(arguments);
                    args.push($dialog);
                    callback.apply(this, args);
                }).always(function () {
                    $dialog.parent().removeClass("loading loading_hiddenContents");
                });
            },
            clearItemsToAdd: function ($dialog) {
                if (!$dialog) {
                    $dialog = this.getDialog();
                }

                $dialog.data("items-to-add", []);
            },
            addCloseDialogIfRequired: function ($dialog) {
                if (!$dialog) {
                    $dialog = this.getDialog();
                }

                if ($dialog.data("onClose")) {
                    return;
                }

                var that = this;

                $dialog.on("dialogbeforeclose", function (e, ui) {
                    var $this = $(this),
                        itemsToAdd = $this.data("items-to-add") || [];

                    if (
                        itemsToAdd.length &&
                        confirm(
                            ss.i18n.sprintf(
                                ss.i18n._t(
                                    "GridField_AddExistingPicker.CONFIRM",
                                    "Would you like to add %s selected items to the current list?",
                                ),
                                itemsToAdd.length,
                            ),
                        )
                    ) {
                        that.addItems($this, itemsToAdd, "", function () {
                            $this.data("grid").reload();
                        });
                    } else {
                        that.clearItemsToAdd($this);
                    }
                });

                $dialog.data("onClose", true);
            },
        });

        function undoItem(id, link, title) {
            var $item = $('.add-existing-picker-items[data-undo-link="' + link + '"]').find(
                '.add-existing-picker-item--link[data-id="' + id + '"]',
            );

            $item.undoItem(id, true, function (data, status, xhr, $dialog) {
                $dialog.data("grid").reload();
                jQuery.noticeRemove($(".notice-item.add-existing-picker-item--undo-holder"));
                statusMessage(
                    ss.i18n.sprintf(
                        ss.i18n._t("GridField_AddExistingPicker.ITEM_REMOVED", "%s has been removed."),
                        title,
                    ),
                );
                $item.parent("li:first").show();
            });
        }

        $(".add-existing-picker-item--undo-holder .add-existing-picker-item--undo").entwine({
            onclick: function (e) {
                var id = this.data("id"),
                    link = this.data("undoLink"),
                    title = this.data("title");

                undoItem(id, link, title);

                e.preventDefault();
            },
        });

        $(".add-existing-search-dialog .add-existing-search-items.add-existing-picker-items a").entwine({
            onclick: function (e) {
                // Otherwise we get an infinite loop
                e.preventDefault();
                e.stopPropagation();

                var $button = this.getButton(),
                    $dialog = $button.getDialog(),
                    $grid = $dialog.data("grid"),
                    $li = this.parent(),
                    items = $dialog.data("items-to-add") || [],
                    id = this.data("id");

                if ($grid && $grid.hasClass("ss-gridfield-add-existing-picker_async")) {
                    var undoLink = $dialog.find(".add-existing-picker-items").data("undo-link"),
                        title = this.text();

                    $li.addClass("loading add-existing-picker-item_toAdd");

                    $button.addItems(
                        $dialog,
                        [id],
                        "",
                        function (data) {
                            var keep = data && data.hasOwnProperty("keep") && data.keep;

                            if ($dialog.data("grid")) {
                                $dialog.data("grid").reload();
                            }

                            $li.removeClass("add-existing-picker-item_toAdd loading");

                            if (!keep) {
                                $li.hide();
                            }

                            jQuery.noticeAdd({
                                text: ss.i18n.sprintf(
                                    ss.i18n._t("GridField_AddExistingPicker.ITEM_ADDED", "%s has been added"),
                                    title,
                                ),
                                actions: [
                                    {
                                        label: ss.i18n._t("UNDO", "Undo"),
                                        onClick: () => {
                                            undoItem(id, undoLink, title);
                                        },
                                    },
                                ],
                                type: "add-existing-picker-item--undo-holder",
                                stayTime: 5000,
                            });
                        },
                        true,
                    );
                } else {
                    $button.addCloseDialogIfRequired($dialog);

                    if (items.indexOf(id) === -1) {
                        items.push(id);
                        $li.addClass("add-existing-picker-item_toAdd");
                    } else {
                        items.splice(items.indexOf(id), 1);
                        $li.removeClass("add-existing-picker-item_toAdd");
                    }

                    $dialog.data("items-to-add", items);
                }

                e.preventDefault();
            },
            getButton: function () {
                return this.closest(".add-existing-search-dialog").find(
                    ".add-existing-picker-actions--add-items:first",
                );
            },
            undoItem: function (id, async, callback) {
                if (!id) id = this.data("id");

                var $button = this.getButton();

                $button.undoItems([id], null, "", callback, async);
            },
        });
    });
})(jQuery);
