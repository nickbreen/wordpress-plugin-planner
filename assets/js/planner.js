jQuery(function($) {
    $('#calendar').fullCalendar($.extend({
        eventRender: function(event, element) {
            element.append(event.content);
        }
    }, planner.calendar));
});
