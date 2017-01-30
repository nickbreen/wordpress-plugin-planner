jQuery(function($) {
    var storage = sessionStorage;
    var key = 'calendar.defaultDate';
    var date = storage.getItem(key);
    if (date)
        planner.calendar.defaultDate = date;

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
            storage.setItem(key, view.start);
        },
        customButtons: {
            booking: ajaxDialog(planner.booking),
            plan: {
                click: function (event) {
                    if (planner.calendar.customButtons.plan.url)
                        window.location = planner.calendar.customButtons.plan.url
                }
            },
            driver: ajaxDialog(planner.driver),
            vehicle: ajaxDialog(planner.vehicle),
        }
    }, planner.calendar)

    var calendar = $('#calendar').fullCalendar(options);

});
