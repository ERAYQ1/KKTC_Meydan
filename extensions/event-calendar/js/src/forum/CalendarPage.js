import app from 'flarum/forum/app';
import Page from 'flarum/common/components/Page';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Button from 'flarum/common/components/Button';
import Link from 'flarum/common/components/Link';

const WEEKDAYS = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];

export default class CalendarPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);

    const today = new Date();
    this.year = today.getFullYear();
    this.month = today.getMonth();
    this.events = [];
    this.loading = true;

    this.loadEvents();
  }

  loadEvents() {
    this.loading = true;

    const start = new Date(this.year, this.month, 1);
    const end = new Date(this.year, this.month + 1, 0, 23, 59, 59);

    app.store
      .find('events', {
        filter: { start: start.toISOString(), end: end.toISOString() },
      })
      .then((events) => {
        this.events = events;
        this.loading = false;
        m.redraw();
      });
  }

  changeMonth(delta) {
    this.month += delta;

    if (this.month < 0) {
      this.month = 11;
      this.year -= 1;
    } else if (this.month > 11) {
      this.month = 0;
      this.year += 1;
    }

    this.loadEvents();
  }

  eventsOnDay(day) {
    return this.events.filter((event) => {
      const start = new Date(event.attribute('eventStartAt'));
      const end = event.attribute('eventEndAt') ? new Date(event.attribute('eventEndAt')) : start;
      const cellDate = new Date(this.year, this.month, day);

      return cellDate >= new Date(start.getFullYear(), start.getMonth(), start.getDate()) && cellDate <= new Date(end.getFullYear(), end.getMonth(), end.getDate());
    });
  }

  view() {
    const daysInMonth = new Date(this.year, this.month + 1, 0).getDate();
    const firstWeekday = (new Date(this.year, this.month, 1).getDay() + 6) % 7; // Monday = 0
    const cells = [];

    for (let i = 0; i < firstWeekday; i++) {
      cells.push(null);
    }
    for (let day = 1; day <= daysInMonth; day++) {
      cells.push(day);
    }

    return (
      <div className="CalendarPage container">
        <div className="CalendarPage-header">
          <Button icon="fas fa-chevron-left" onclick={() => this.changeMonth(-1)} />
          <h2>
            {this.year}-{String(this.month + 1).padStart(2, '0')}
          </h2>
          <Button icon="fas fa-chevron-right" onclick={() => this.changeMonth(1)} />
        </div>

        {this.loading ? (
          <LoadingIndicator />
        ) : (
          <div className="CalendarPage-grid">
            {WEEKDAYS.map((d) => (
              <div className="CalendarPage-weekday">{d}</div>
            ))}
            {cells.map((day) => (
              <div className={`CalendarPage-cell ${day ? '' : 'CalendarPage-cell--empty'}`}>
                {day && <div className="CalendarPage-day">{day}</div>}
                {day &&
                  this.eventsOnDay(day).map((event) => (
                    <Link href={app.route.discussion(event)} className="CalendarPage-event">
                      {event.title()}
                    </Link>
                  ))}
              </div>
            ))}
          </div>
        )}
      </div>
    );
  }
}
