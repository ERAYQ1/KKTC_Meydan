import Component from 'flarum/common/Component';

export default class StarRating extends Component {
  view() {
    const { value, interactive, onchange } = this.attrs;
    const stars = [1, 2, 3, 4, 5];

    return (
      <span className="BusinessReviews-stars">
        {stars.map((n) =>
          interactive ? (
            <i
              className={`fas fa-star BusinessReviews-starPicker ${n <= value ? 'active' : ''}`}
              onclick={() => onchange(n)}
            />
          ) : (
            <i className={`fas fa-star ${n <= value ? '' : 'inactive'}`} style={{ opacity: n <= value ? 1 : 0.3 }} />
          )
        )}
      </span>
    );
  }
}
