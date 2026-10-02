<?php
foreach(['campus','rusun'] as $name) {
    $file=__DIR__.'/../.runtime/'.$name.'.image';
    if(!is_file($file)) continue;
    $image=@imagecreatefromstring(file_get_contents($file)); if(!$image) continue;
    $ratio=min(1,1600/imagesx($image)); $target=imagecreatetruecolor((int)(imagesx($image)*$ratio),(int)(imagesy($image)*$ratio));
    imagecopyresampled($target,$image,0,0,0,0,imagesx($target),imagesy($target),imagesx($image),imagesy($image));
    $directory=__DIR__.'/../storage/app/private/media'; if(!is_dir($directory))mkdir($directory,0755,true);
    imagewebp($target,$directory.'/'.$name.'.webp',85); imagedestroy($target); imagedestroy($image); echo "$name converted\n";
}
