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

    var options = $.extend(true, {
        eventRender: function(event, element) {
            element.find('.fc-content').append(event.content);
        },
        viewRender: function (view, element) {
            storage.setItem(key, view.start);
        },
        customButtons: {
            plan: {
                click: function (event) {
                    console.log(planner.calendar.customButtons.plan.url);
                    if (planner.calendar.customButtons.plan.url)
                        window.location = planner.calendar.customButtons.plan.url
                }
            },
            driver: {
                click: function (event) {
                    $('#drivers').dialog($.extend(true, {
                        buttons: {
                            "new": {
                                click: function (event) {
                                    console.log(planner.dialogs.drivers.buttons.new.url);
                                    if (planner.dialogs.drivers.buttons.new.url)
                                        window.location = planner.dialogs.drivers.buttons.new.url;
                                }
                            }
                        }
                    }, planner.dialogs.drivers, dialogOptions));
                }
            },
            vehicle: {
                click: function (event) {
                    $('#vehicles').dialog($.extend(true, {
                        buttons: {
                            "new": {
                                click: function (event) {
                                    console.log(planner.dialogs.vehicles.buttons.new.url);
                                    if (planner.dialogs.vehicles.buttons.new.url)
                                        window.location = planner.dialogs.vehicles.buttons.new.url;
                                }
                            }
                        }
                    }, planner.dialogs.vehicles, dialogOptions));
                }
            }
        }
    }, planner.calendar)

    $('#calendar').fullCalendar(options);



});
