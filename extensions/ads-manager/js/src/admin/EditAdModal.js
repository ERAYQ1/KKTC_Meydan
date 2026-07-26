import app from 'flarum/admin/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import Stream from 'flarum/common/utils/Stream';

function trans(key) {
  return app.translator.trans(`kktcmeydan-ads-manager.admin.modal.${key}`);
}

export default class EditAdModal extends Modal {
  static isDismissible = true;

  oninit(vnode) {
    super.oninit(vnode);

    const ad = this.attrs.ad;

    this.titleValue = Stream(ad ? ad.attribute('title') : '');
    this.imageUrl = Stream(ad ? ad.attribute('imageUrl') : '');
    this.targetUrl = Stream(ad ? ad.attribute('targetUrl') : '');
    this.targetCategorySlug = Stream(ad ? ad.attribute('targetCategorySlug') || '' : '');
    this.targetDistrictSlug = Stream(ad ? ad.attribute('targetDistrictSlug') || '' : '');
    this.targetUniversitySlug = Stream(ad ? ad.attribute('targetUniversitySlug') || '' : '');
    this.position = Stream(ad ? ad.attribute('position') : 'discussion_list');
    this.isActive = Stream(ad ? ad.attribute('isActive') : true);

    this.loading = false;
  }

  className() {
    return 'EditAdModal Modal--small';
  }

  title() {
    return this.attrs.ad ? trans('edit_title') : trans('add_title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form-group">
          <label>{trans('title_label')}</label>
          <input className="FormControl" bidi={this.titleValue} />
        </div>
        <div className="Form-group">
          <label>{trans('image_url_label')}</label>
          <input className="FormControl" bidi={this.imageUrl} />
          <p className="helpText">{trans('image_url_help')}</p>
        </div>
        <div className="Form-group">
          <label>{trans('target_url_label')}</label>
          <input className="FormControl" bidi={this.targetUrl} />
        </div>
        <div className="Form-group">
          <label>{trans('target_category_label')}</label>
          <input className="FormControl" bidi={this.targetCategorySlug} placeholder="ör. sorun-bildir" />
        </div>
        <div className="Form-group">
          <label>{trans('target_district_label')}</label>
          <input className="FormControl" bidi={this.targetDistrictSlug} placeholder="ör. girne" />
        </div>
        <div className="Form-group">
          <label>{trans('target_university_label')}</label>
          <input className="FormControl" bidi={this.targetUniversitySlug} placeholder="ör. dau" />
        </div>
        <p className="helpText">{trans('target_help')}</p>
        <div className="Form-group">
          <label>
            <input type="checkbox" checked={this.isActive()} onchange={(e) => this.isActive(e.target.checked)} /> {trans('is_active_label')}
          </label>
        </div>
        <div className="Form-group">
          <Button className="Button Button--primary" type="submit" loading={this.loading}>
            {trans('save_button')}
          </Button>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();
    this.loading = true;

    const data = {
      title: this.titleValue(),
      imageUrl: this.imageUrl(),
      targetUrl: this.targetUrl(),
      targetCategorySlug: this.targetCategorySlug() || null,
      targetDistrictSlug: this.targetDistrictSlug() || null,
      targetUniversitySlug: this.targetUniversitySlug() || null,
      position: this.position(),
      isActive: this.isActive(),
    };

    const ad = this.attrs.ad || app.store.createRecord('ads');

    ad.save(data).then(() => {
      this.hide();
      if (this.attrs.onsaved) this.attrs.onsaved();
    }, this.loaded.bind(this));
  }
}
