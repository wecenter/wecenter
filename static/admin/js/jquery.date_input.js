DateInput = (function($) { // Localise the $ function

function DateInput(el, opts) {
  if (typeof(opts) != "object") opts = {};
  $.extend(this, DateInput.DEFAULT_OPTS, opts);
  
  this.input = $(el);
  this.bindMethodsToObj("show", "hide", "hideIfClickOutside", "keydownHandler", "selectDate");
  
  this.build();
  this.selectDate();
  this.hide();
};
DateInput.DEFAULT_OPTS = {
  month_names: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
  short_month_names: ["一", "二", "三", "四", "五", "六", "七", "八", "九", "十", "十一", "十二"],
  short_day_names: ["一", "二", "三", "四", "五", "六", "日"],
  start_of_week: 0
};
DateInput.prototype = {
  build: function() {
    var monthNav = $('<p class="month_nav">' +
      '<span class="buttonx prev" title="[Page-Up]">&#171;</span>' +
      ' <span class="month_name"></span> ' +
      '<span class="buttonx next" title="[Page-Down]">&#187;</span>' +
      '</p>');
    this.monthNameSpan = $(".month_name", monthNav);
    $(".prev", monthNav).click(this.bindToObj(function() { this.moveMonthBy(-1); }));
    $(".next", monthNav).click(this.bindToObj(function() { this.moveMonthBy(1); }));
    
    var yearNav = $('<p class="year_nav">' +
      '<span class="buttonx prev" title="[Ctrl+Page-Up]">&#171;</span>' +
      ' <span class="year_name"></span> ' +
      '<span class="buttonx next" title="[Ctrl+Page-Down]">&#187;</span>' +
      '</p>');
    this.yearNameSpan = $(".year_name", yearNav);
    $(".prev", yearNav).click(this.bindToObj(function() { this.moveMonthBy(-12); }));
    $(".next", yearNav).click(this.bindToObj(function() { this.moveMonthBy(12); }));
    
    var nav = $('<div class="nav"></div>').append(monthNav, yearNav);
    
    var tableShell = "<table><thead><tr>";
    $(this.adjustDays(this.short_day_names)).each(function() {
      tableShell += "<th>" + this + "</th>";
    });
    tableShell += "</tr></thead><tbody></tbody></table>";
    
    this.dateSelector = this.rootLayers = $('<div class="date_selector"></div>').append(nav, tableShell).insertAfter(this.input);
    
    if ($.browser.msie && $.browser.version < 7) {
      // The ieframe is a hack which works around an IE <= 6 bug where absolutely positioned elements
      // appear behind select boxes. Putting an iframe over the top of the select box prevents this.
      this.ieframe = $('<iframe class="date_selector_ieframe" frameborder="0" src="#"></iframe>').insertBefore(this.dateSelector);
      this.rootLayers = this.rootLayers.add(this.ieframe);
      
      // IE 6 only does :hover on A elements
      $(".buttonx", nav).mouseover(function() { $(this).addClass("hover") });
      $(".buttonx", nav).mouseout(function() { $(this).removeClass("hover") });
    };
    
    this.tbody = $("tbody", this.dateSelector);
    
    this.input.change(this.bindToObj(function() { this.selectDate(); }));
    this.selectDate();
  },

  selectMonth: function(date) {
    var newMonth = new Date(date.getFullYear(), date.getMonth(), 1);
    
    if (!this.currentMonth || !(this.currentMonth.getFullYear() == newMonth.getFullYear() &&
                                this.currentMonth.getMonth() == newMonth.getMonth())) {
      // We have moved to a different month and so need to re-draw the table
      this.currentMonth = newMonth;
      
      // Work out the range of days we will draw
      var rangeStart = this.rangeStart(date), rangeEnd = this.rangeEnd(date);
      var numDays = this.daysBetween(rangeStart, rangeEnd);
      var dayCells = "";
      
      // Draw each of the days
      for (var i = 0; i <= numDays; i++) {
        var currentDay = new Date(rangeStart.getFullYear(), rangeStart.getMonth(), rangeStart.getDate() + i, 12, 00);
        
        if (this.isFirstDayOfWeek(currentDay)) dayCells += "<tr>";
        
        if (currentDay.getMonth() == date.getMonth()) {
          dayCells += '<td class="selectable_day" date="' + this.dateToString(currentDay) + '">' + currentDay.getDate() + '</td>';
        } else {
          dayCells += '<td class="unselected_month" date="' + this.dateToString(currentDay) + '">' + currentDay.getDate() + '</td>';
        };
        
        if (this.isLastDayOfWeek(currentDay)) dayCells += "</tr>";
      };
      this.tbody.empty().append(dayCells);
      
      // Write the month and year in the header
      this.monthNameSpan.empty().append(this.monthName(date));
      this.yearNameSpan.empty().append(this.currentMonth.getFullYear());
      
      $(".selectable_day", this.tbody).click(this.bindToObj(function(event) {
        this.changeInput($(event.target).attr("date"));
      }));
      
      //$("td[date=" + this.dateToString(new Date()) + "]", this.tbody).addClass("today");
      $("td[date='" + this.dateToString(new Date()) + "']", this.tbody).addClass("today");
      
      $("td.selectable_day", this.tbody).mouseover(function() { $(this).addClass("hover") });
      $("td.selectable_day", this.tbody).mouseout(function() { $(this).removeClass("hover") });
    };
    
    $('.selected', this.tbody).removeClass("selected");
    $('td[date="' + this.selectedDateString + '"]', this.tbody).addClass("selected");
  },
  
  // Select a particular date. If the date is not specified it is read from the input. If no date is
  // found then the current date is selected. The selectMonth() function is responsible for actually
  // selecting a particular date.
  selectDate: function(date) {
    if (typeof(date) == "undefined") {
      date = this.stringToDate(this.input.val());
    };
    if (!date) date = new Date();
    
    this.selectedDate = date;
    this.selectedDateString = this.dateToString(this.selectedDate);
    this.selectMonth(this.selectedDate);
  },
  
  // Write a date string to the input and hide. Trigger the change event so we know to update the
  // selectedDate.
  changeInput: function(dateString) {
    this.input.val(dateString).change();
    this.hide();
  },
  
  show: function() {
    this.rootLayers.css("display", "block");
    $([window, document.body]).click(this.hideIfClickOutside);
    this.input.unbind("focus", this.show);
    $(document.body).keydown(this.keydownHandler);
    this.setPosition();
  },
  
  hide: function() {
    this.rootLayers.css("display", "none");
    $([window, document.body]).unbind("click", this.hideIfClickOutside);
    this.input.focus(this.show);
    $(document.body).unbind("keydown", this.keydownHandler);
  },
  
  // We should hide the date selector if a click event happens outside of it
  hideIfClickOutside: function(event) {
    if (event.target != this.input[0] && !this.insideSelector(event)) {
      this.hide();
    };
  },
  
  // Returns true if the given event occurred inside the date selector
  insideSelector: function(event) {
    var offset = this.dateSelector.position();
    offset.right = offset.left + this.dateSelector.outerWidth();
    offset.bottom = offset.top + this.dateSelector.outerHeight();
    
    return event.pageY < offset.bottom &&
           event.pageY > offset.top &&
           event.pageX < offset.right &&
           event.pageX > offset.left;
  },
  
  // Respond to various different keyboard events
  keydownHandler: function(event) {
    switch (event.keyCode)
    {
      case 9: // tab
      case 27: // esc
        this.hide();
        return;
      break;
      case 13: // enter
        this.changeInput(this.selectedDateString);
      break;
      case 33: // page up
        this.moveDateMonthBy(event.ctrlKey ? -12 : -1);
      break;
      case 34: // page down
        this.moveDateMonthBy(event.ctrlKey ? 12 : 1);
      break;
      case 38: // up
        this.moveDateBy(-7);
      break;
      case 40: // down
        this.moveDateBy(7);
      break;
      case 37: // left
        this.moveDateBy(-1);
      break;
      case 39: // right
        this.moveDateBy(1);
      break;
      default:
        return;
    }
    event.preventDefault();
  },
  
  stringToDate: function(string) {
    var matches;
    if (matches = string.match(/^(\d{1,2}) ([^\s]+) (\d{4,4})$/)) {
      return new Date(matches[3], this.shortMonthNum(matches[2]), matches[1], 12, 00);
    } else {
      return null;
    };
  },
  
  dateToString: function(date) {
    var month = (date.getMonth() + 1).toString();
    var dom = date.getDate().toString();
    if (month.length == 1) month = "0" + month;
    if (dom.length == 1) dom = "0" + dom;
    return date.getFullYear() + "-" + month + "-" + dom;
  },
  
  setPosition: function() {
    var offset = this.input.offset();
    this.rootLayers.css({
      top: offset.top + this.input.outerHeight(),
      left: offset.left
    });
    
    if (this.ieframe) {
      this.ieframe.css({
        width: this.dateSelector.outerWidth(),
        height: this.dateSelector.outerHeight()
      });
    };
  },
  
  // Move the currently selected date by a particular number of days
  moveDateBy: function(amount) {
    var newDate = new Date(this.selectedDate.getFullYear(), this.selectedDate.getMonth(), this.selectedDate.getDate() + amount);
    this.selectDate(newDate);
  },
  
  // Move the month of the currently selected date by a particular number of months. If we are moving
  // to a month which does not have enough days to represent the current day-of-month, then we 
  // default to the last day of the month.
  moveDateMonthBy: function(amount) {
    var newDate = new Date(this.selectedDate.getFullYear(), this.selectedDate.getMonth() + amount, this.selectedDate.getDate());
    if (newDate.getMonth() == this.selectedDate.getMonth() + amount + 1) {
      // We have moved too far. For instance 31st March + 1 month = 1st May, not 30th April
      newDate.setDate(0);
    };
    this.selectDate(newDate);
  },
  
  // Move the currently displayed month by a certain amount. This does *not* move the currently
  // selected date, so we end up viewing a month with no visibly selected date.
  moveMonthBy: function(amount) {
    var newMonth = new Date(this.currentMonth.getFullYear(), this.currentMonth.getMonth() + amount, this.currentMonth.getDate());
    this.selectMonth(newMonth);
  },
  
  monthName: function(date) {
    return this.month_names[date.getMonth()];
  },
  
  // A hack to make "this" refer to this object instance when inside the given function
  bindToObj: function(fn) {
    var self = this;
    return function() { return fn.apply(self, arguments) };
  },
  
  // See above
  bindMethodsToObj: function() {
    for (var i = 0; i < arguments.length; i++) {
      this[arguments[i]] = this.bindToObj(this[arguments[i]]);
    };
  },
  
  // Finds out the array index of a particular value in that array
  indexFor: function(array, value) {
    for (var i = 0; i < array.length; i++) {
      if (value == array[i]) return i;
    };
  },
  
  // Finds the number of a given month name
  monthNum: function(month_name) {
    return this.indexFor(this.month_names, month_name);
  },
  
  // Finds the number of a given short month name
  shortMonthNum: function(month_name) {
    return this.indexFor(this.short_month_names, month_name);
  },
  
  // Finds the number of a given day name
  shortDayNum: function(day_name) {
    return this.indexFor(this.short_day_names, day_name);
  },
  
  // Works out the number of days between two dates
  daysBetween: function(start, end) {
    start = Date.UTC(start.getFullYear(), start.getMonth(), start.getDate());
    end = Date.UTC(end.getFullYear(), end.getMonth(), end.getDate());
    return (end - start) / 86400000;
  },
  
  /*
  changeDayTo: Given a date, move along the date line in the given direction until we reach the
  desired day of week.
  
  The maths is a bit complex, here's an explanation.
  
  Think of a continuous repeating number line like:
  
  .. 5 6 0 1 2 3 4 5 6 0 1 2 3 4 5 6 0 1 ..
  
  We are essentially trying to find the difference between two numbers
  on the line in one direction (dictated by the sign of direction variable).
  Unfortunately Javascript's modulo operator works such that -5 % 7 = -5,
  instead of -5 % 7 = 2, so we need to only work with the positives.
  
  To find the difference between 1 and 4, going backwards, we can treat 1
  as (1 + 7) = 8, so the different is |8 - 4| = 4. If we don't cross the 
  boundary between 0 and 6, for instance to find the backwards difference
  between 5 and 2, |(5 + 7) - 2| = |12 - 2| = 10. And 10 % 7 = 3.
  
  Going forwards, to find the difference between 4 and 1, we again treat 1
  as (1 + 7) = 8, and the difference is |4 - 8| = 4. If we don't cross the
  boundary, the difference between 2 and 5 is |2 - (5 + 7)| = |2 - 12| = 10.
  And 10 % 7 = 3.
  
  Once we have the positive difference in either direction represented as a
  absolute value, we can multiply it by the direction variable to get the difference
  in the desired direction.
  
  We can condense the two methods into a single equation:
  
    backwardsDifference = direction * (|(currentDayNum + 7) - dayOfWeek| % 7)
                        = direction * (|currentDayNum - dayOfWeek + 7|  % 7)
    
     forwardsDifference = direction * (|currentDayNum - (dayOfWeek + 7)| % 7)
                        = direction * (|currentDayNum - dayOfWeek - 7| % 7)
    
    (The two equations now differ only by the +/- 7)
    
             difference = direction * (|currentDayNum - dayOfWeek - (direction * 7)| % 7)
  */
  changeDayTo: function(dayOfWeek, date, direction) {
    var difference = direction * (Math.abs(date.getDay() - dayOfWeek - (direction * 7)) % 7);
    return new Date(date.getFullYear(), date.getMonth(), date.getDate() + difference);
  },
  
  // Given a date, return the day at the start of the week *before* this month
  rangeStart: function(date) {
    return this.changeDayTo(this.start_of_week, new Date(date.getFullYear(), date.getMonth()), -1);
  },
  
  // Given a date, return the day at the end of the week *after* this month
  rangeEnd: function(date) {
    return this.changeDayTo((this.start_of_week - 1) % 7, new Date(date.getFullYear(), date.getMonth() + 1, 0), 1);
  },
  
  // Is the given date the first day of the week?
  isFirstDayOfWeek: function(date) {
    return date.getDay() == this.start_of_week;
  },
  
  // Is the given date the last day of the week?
  isLastDayOfWeek: function(date) {
    return date.getDay() == (this.start_of_week - 1) % 7;
  },
  
  // Adjust a given array of day names to begin with the configured start-of-week
  adjustDays: function(days) {
    var newDays = [];
    for (var i = 0; i < days.length; i++) {
      newDays[i] = days[(i + this.start_of_week) % 7];
    };
    return newDays;
  }
};

$.fn.date_input = function(opts) {
  return this.each(function() { new DateInput(this, opts); });
};
$.date_input = { initialize: function(opts) {
  $("input.date_input").date_input(opts);
} };

return DateInput;
})(jQuery); // End localisation of the $ function
