import app from 'flarum/admin/app';

app.initializers.add('kktcmeydan-report-status', () => {
  app.extensionData.for('kktcmeydan-report-status').registerPermission(
    {
      icon: 'fas fa-triangle-exclamation',
      label: app.translator.trans('kktcmeydan-report-status.admin.permissions.edit_report_status_label'),
      permission: 'discussion.editReportStatus',
    },
    'moderate',
    95
  );
});
