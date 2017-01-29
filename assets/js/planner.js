jQuery(function($) {
    var storage = sessionStorage;
    var key = 'calendar.defaultDate';
    var date = storage.getItem(key);
    if (date)
        planner.calendar.defaultDate = date;

    var dialogOptions = {
        appendTo: '#calendar',
        modal: true,
        width: 16 * 8 * 4 + 1 * 3,
    };

    var customButton = function (dialogSelector, buttons) {
        return {
            click: function (event) {
                $(dialogSelector).dialog($.extend(true, {
                    buttons: {
                        "new": {
                            click: function (event) {
                                if (buttons.new.url)
                                    window.location = buttons.new.url;
                            }
                        }
                    },
                    focus: function (event) {
                        var dialog = $(this);
                        dialog.find('li').draggable({
                            appendTo: '#calendar',
                            containment: '#calendar',
                            stack: '#calendar',
                            zIndex: 1,
                            helper: "clone",
                            start: function (event, ui) {
                                dialog.dialog('close');
                            }
                        });
                    }
                }, planner.dialogs.vehicles, dialogOptions));
            }
        };
    };

    var options = $.extend(true, {
        eventRender: function(event, element) {
            // console.log(element);
            element.data(event.data);
            element.find('.fc-content').append(event.content);
            element.droppable({
                accept: 'li.driver, li.vehicle',
                activeClass: 'assignable', // <1.11
                hoverClass: 'assign',
                classes: { // 1.2<
                    'ui-droppable-active': 'assignable',
                    'ui-droppable-hover': 'assign',
                },
                drop: function (event, ui) {
                    $.ajax({
                        url: $(this).data('url'),
                        context: this,
                        method: 'GET',
                        headers: {
                            'X-WP-Nonce': $(this).data('nonce')
                        },
                        contentType: 'application/json; charset=UTF-8',
                        beforeSend: console.log,
                        success: function (data, textStatus, jqXHR) {
                            // console.log(data, textStatus, jqXHR);
                            // console.log(ui.draggable.prop('dataset'))
                            $.ajax({
                                url: $(this).data('url'),
                                context: this,
                                method: 'PATCH',
                                headers: {
                                    'X-WP-Nonce': $(this).data('nonce')
                                },
                                contentType: 'application/json; charset=UTF-8',
                                processData: false,
                                data: JSON.stringify({
                                    driver: ui.draggable.is('li.driver') ? [parseInt(ui.draggable.prop('dataset').id)].concat(data.driver) : data.driver,
                                    vehicle: ui.draggable.is('li.vehicle') ? [parseInt(ui.draggable.prop('dataset').id)].concat(data.vehicle) : data.vehicle
                                }),
                                beforeSend: console.log,
                                success: function (data, textStatus, jqXHR) {
                                    // console.log(data, textStatus, jqXHR, this);
                                    $('#calendar').fullCalendar('refetchEvents');
                                }
                            });
                        }
                    })
                }
            });
        },
        viewRender: function (view, element) {
            storage.setItem(key, view.start);
        },
        customButtons: {
            plan: {
                click: function (event) {
                    if (planner.calendar.customButtons.plan.url)
                        window.location = planner.calendar.customButtons.plan.url
                }
            },
            driver: customButton('#drivers', planner.dialogs.drivers.buttons),
            vehicle: customButton('#vehicles', planner.dialogs.vehicles.buttons),
        }
    }, planner.calendar)

    $('#calendar').fullCalendar(options);

});
