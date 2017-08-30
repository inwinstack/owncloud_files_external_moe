<?php

?>
<!DOCTYPE html>
<style type="text/css">
#closeWizard{
    position:absolute;
    right:10px;
}
h1 {
  font-family: "Merriweather", serif;
  padding: 20px 0px;
  font-size: 32px;
}

h3 {
  font-family: "Merriweather", serif;
  font-size: 28px;
  padding: 20px 0px;
}
#file_external_moe_help {
  font-size: 20px;
  padding: 20px;
}
.img {
  max-width: 100%;
  max-height: 100%;
  display:table-cell;
  vertical-align:middle;
  margin:auto;
  padding: 20px;

}
</style>
<div>
    <a id="closeWizard" class="close">
        <img class="svg" src="<?php print_unescaped(OCP\Util::imagePath('core', 'actions/close.svg')); ?>">
    </a>
    <h1>我要怎麼加入我的外部儲存空間?</h1>
    <h3>以Google Drive為例</h3>
    <p id="file_external_moe_help">
      1.展開帳號設定選單，點擊 <strong>[帳號資訊]</strong> 開啟帳號設定頁面。
    </p>
    <img class="img" src="<?php print_unescaped(image_path('files_external_moe', 'step1.png')); ?>">
    <p id="file_external_moe_help">
      2.點選左邊清單中的外部儲存選項，可快速移動至設定畫面，並點選 <strong>[Google Drive]</strong>。
    </p>
    <img class="img" src="<?php print_unescaped(image_path('files_external_moe', 'step2.png')); ?>">
    <p id="file_external_moe_help">
      3.按下 <strong>[新增]</strong>。
    </p>
    <img class="img" src="<?php print_unescaped(image_path('files_external_moe', 'step3.png')); ?>">
    <p id="file_external_moe_help">
      4.輸入你的Google 帳號。
    </p>
    <img class="img" src="<?php print_unescaped(image_path('files_external_moe', 'step4.png')); ?>">
    <p id="file_external_moe_help">
      5.點擊允許，就成功把你Google Drive中的檔案加入到你的雲端儲存空間囉！
    </p>
</div>
