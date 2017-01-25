jQuery(function($) {
    var storage = sessionStorage;
    var key = 'calendar.defaultDate';
    var date = storage.getItem(key);
    if (date)
        planner.calendar.defaultDate = date;

    var options = $.extend({
        eventRender: function(event, element) {
            element.find('.fc-content').append(event.content);
        },
        viewRender: function (view, element) {
            storage.setItem(key, view.start);
        }
    }, planner.calendar)
    $('#calendar').fullCalendar(options);

});
