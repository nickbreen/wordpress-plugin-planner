jQuery(function($) {
    var storage = sessionStorage;
    var key = 'calendar.defaultDate';
    var date = storage.getItem(key);
    if (date)
        planner.calendar.defaultDate = date;
    var options = $.extend(true, {
        theme: true,
        eventRender: function(event, element) {
            // element.data(event.data);
            console.log(event)
            element.find('td').last().append(event.content)
        },
        viewRender: function (view, element) {
            storage.setItem(key, view.start);
        },
    }, planner.calendar)

    var calendar = $('#calendar').fullCalendar(options);
});
