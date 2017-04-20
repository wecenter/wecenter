#!/bin/bash

[ $DEBUG ] && set -x

Dirs="system/config"
PermanentDir="/data"
AppDir="/app"
UserCfg="${PermanentDir}/system/config/config.yaml"
InstallFile="${AppDir}/install/index.php"
#UpgradeFile="${AppDir}/www/upgrade.php"


# 在持久化存储中创建需要的目录
for d in $Dirs
do
  # 检测是否包含子目录
  subdir=`dirname $d`

  if [ ! -d ${PermanentDir}/${d} ] ;then
  
    if [ "$subdir" == "." ];then
      [ -d ${AppDir}/${d} ] && mv ${AppDir}/${d} ${PermanentDir}/${d} || mkdir -pv ${PermanentDir}/${d}
    else
      [ ! -d ${PermanentDir}/$subdir ] && mkdir -pv ${PermanentDir}/$subdir
      [ -d ${AppDir}/${d} ] && mv ${AppDir}/${d} ${PermanentDir}/$subdir
    fi
  
  else
    mv ${AppDir}/${d} ${AppDir}/${d}.bak
  fi
  ln -s ${PermanentDir}/${d} ${AppDir}/${d}
done

# 如果存在my.php 清理install.php和upgrade.php 文件
if [ -f $UserCfg ];then
  [ -f $InstallFile ] && rm -f $InstallFile
  #[ -f $UpgradeFile ] && rm -f $UpgradeFile
fi


# 启动web server
vendor/bin/heroku-php-nginx
