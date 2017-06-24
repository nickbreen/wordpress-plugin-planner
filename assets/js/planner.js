jQuery(function($) {
    var storage = sessionStorage;
    var key = 'calendar.defaultDate';
    try {
        var d = storage.getItem(key);
        m = $.fullCalendar.moment(d, moment.ISO_8601);
        if (m && m.isValid()) {
            planner.calendar.defaultDate = m;
        } else {
            console.error("Invalid Moment", m.creationData()); 
            storage.removeItem(key);
        }
    } catch (e) {
        console.error(e);
    }
    var ajaxDialog = function (options) {
        return {
            click: function (event) {
                var dialog = $('<div/>').attr('title', options.dialog.title).append(options.dialog.content);
                $.ajax($.extend(
                    true,
                    options.rest,
                    options.rest.data.start ? { data: { start: $('#calendar').fullCalendar('getView').start.toISOString() }} : {},
                    options.rest.data.end ? { data: { end: $('#calendar').fullCalendar('getView').end.toISOString() }} : {},
                    {
                        success: function (data, textStatus, jqXHR) {
                            dialog.append(data);
                            dialog.dialog(
                                    $.extend(
                                        true,
                                        options.dialog,
                                        {
                                            buttons: {
                                                "new": {
                                                    click: function (event) {
                                                        if (options.dialog.buttons.new.url)
                                                            window.location = options.dialog.buttons.new.url;
                                                    }
                                                }
                                            },
                                            focus: function (event) {
                                                var dialog = $(this);
                                                dialog.find('li.driver, li.vehicle').draggable({ //, tr.booking
                                                    appendTo: '.planner',
                                                    containment: '#calendar',
                                                    stack: '.ui-dialog, .ui-dialog-content',
                                                    zIndex: 50,
                                                    helper: "clone"
                                                });
                                            },
                                            appendTo: '.planner',
                                            width: 'auto'
                                        }
                                    )
                                )
                        }
                    }
                ));
            }
        };
    };

    var options = $.extend(true, {
        eventRender: function(event, element) {
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
                        success: function (data, textStatus, jqXHR) {
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
                                success: function (data, textStatus, jqXHR) {
                                    $('#calendar').fullCalendar('refetchEvents');
                                }
                            });
                        }
                    })
                }
            });
        },
        viewRender: function (view, element) {
            try {
                storage.setItem(key, view.start.toISOString());
            } catch (e) {
                console.error(e);
                alert(e.message);
            }
        },
        customButtons: {
            airport: {
                click: function (event) {
                    if (planner.calendar.customButtons.airport.url)
                        window.location = planner.calendar.customButtons.airport.url
                }
            },
            transfer: {
                click: function (event) {
                    if (planner.calendar.customButtons.transfer.url)
                        window.location = planner.calendar.customButtons.transfer.url
                }
            },
            tour: {
                click: function (event) {
                    if (planner.calendar.customButtons.tour.url)
                        window.location = planner.calendar.customButtons.tour.url
                }
            },
            booking: ajaxDialog(planner.booking),
            driver: ajaxDialog(planner.driver),
            vehicle: ajaxDialog(planner.vehicle),
        },
        loading: function (isLoading, view) {
            if (isLoading) {
                jQuery("#calendar .fc-view-container").notify(
                    "Loading" + (view.title ? ' ' + view.title : '') + '\u2026',
                    {
                        autoHide: false,
                        className: "info",
                        position: "top center"
                    }
                )
            } else {
                $('#calendar .notifyjs-wrapper').trigger('notify-hide');
            }
        }
    }, planner.calendar)

    var calendar = $('#calendar').fullCalendar(options);

});
