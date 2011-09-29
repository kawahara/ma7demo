<?php
header('Content-Type: application/xml;charset=UTF-8');

echo '<?xml version="1.0" encoding="UTF-8"?>'
?>
<Module>
  <ModulePrefs title="ma7" description="ma7のサンプルアプリ">
    <Require feature="opensocial-0.8" />
    <Require feature="dynamic-height" />
  </ModulePrefs>
  <Content type="html"><![CDATA[
<?php require(dirname(__FILE__).'/../app/app.php') ?>
]]>
  </Content>
</Module>
