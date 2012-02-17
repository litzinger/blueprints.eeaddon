
BACKUP YOUR DATABASE FIRST!

Go to line 2361 of system/expressionengine/controllers/cp/content_publish.php and add the following just after $settings[$val['field_id']] = $val;

// So 3rd party module tab fields get their data on autosave
if (isset($entry_data[$val['field_id']]))
{
  $settings[$val['field_id']]['field_data'] = $entry_data[$val['field_id']];
}