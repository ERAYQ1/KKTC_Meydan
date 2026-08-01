import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Button from 'flarum/common/components/Button';
import HeaderSecondary from 'flarum/forum/components/HeaderSecondary';
import DutyPharmacyModal from './DutyPharmacyModal';

app.initializers.add('kktcmeydan-duty-pharmacy', () => {
  extend(HeaderSecondary.prototype, 'items', function (items) {
    items.add(
      'dutyPharmacy',
      <Button className="Button Button--link DutyPharmacy-headerButton" onclick={() => app.modal.show(DutyPharmacyModal)}>
        🏥 {app.translator.trans('kktcmeydan-duty-pharmacy.forum.header_button')}
      </Button>,
      40
    );
  });
});
