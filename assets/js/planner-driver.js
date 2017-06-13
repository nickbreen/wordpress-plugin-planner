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
