import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import IndexPage from 'flarum/forum/components/IndexPage';
import Button from 'flarum/common/components/Button';
import DutyPharmacyModal from './DutyPharmacyModal';

app.initializers.add('kktcmeydan-duty-pharmacy', () => {
  extend(IndexPage.prototype, 'sidebarItems', function (items) {
    items.add(
      'dutyPharmacy',
      <Button className="Button DutyPharmacy-sidebarButton" onclick={() => app.modal.show(DutyPharmacyModal)}>
        🏥 {app.translator.trans('kktcmeydan-duty-pharmacy.forum.header_button')}
      </Button>,
      85
    );
  });
});
