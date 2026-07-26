import Component from 'flarum/common/Component';

/**
 * A generic slide-up mobile bottom sheet: backdrop + panel, closes on
 * backdrop tap. Attrs: `title`, `onclose`, children = sheet body.
 */
export default class BottomSheet extends Component {
  view() {
    return (
      <div className="MobileBottomSheet-container">
        <div className="MobileBottomSheet-backdrop" onclick={() => this.attrs.onclose()} />
        <div className="MobileBottomSheet">
          <div className="MobileBottomSheet-handle" />
          {this.attrs.title && <div className="MobileBottomSheet-title">{this.attrs.title}</div>}
          <div className="MobileBottomSheet-body">{this.attrs.children}</div>
        </div>
      </div>
    );
  }
}
