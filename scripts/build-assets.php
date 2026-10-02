<?php
// Fallback build when Node.js/Vite is unavailable: bundles CSS imports and JS into public/build like `vite build` (no minify).
$root=dirname(__DIR__); $out="$root/public/build"; @mkdir("$out/assets",0777,true);
$css=preg_replace_callback('~@import\s+[\'"]\./([^\'"]+)[\'"];~',fn($m)=>file_get_contents("$root/resources/css/".$m[1]),file_get_contents("$root/resources/css/app.css"));
$js=file_get_contents("$root/resources/js/site.js");
array_map('unlink',glob("$out/assets/app-*"));
$cssFile='assets/app-'.substr(md5($css),0,8).'.css'; $jsFile='assets/app-'.substr(md5($js),0,8).'.js';
file_put_contents("$out/$cssFile",$css); file_put_contents("$out/$jsFile",$js);
file_put_contents("$out/manifest.json",json_encode(['resources/css/app.css'=>['file'=>$cssFile,'src'=>'resources/css/app.css','isEntry'=>true,'name'=>'app','names'=>['app.css']],'resources/js/app.js'=>['file'=>$jsFile,'name'=>'app','src'=>'resources/js/app.js','isEntry'=>true]],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo "Built $cssFile, $jsFile\n";
