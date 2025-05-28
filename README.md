# msautocreate
原作者[Byg](https://github.com/realByg)的node版本[office-user-auto-create](https://github.com/realByg/office-user-auto-create)，由[傍晚升起的太阳](https://github.com/wuruiwm)将后端重构为PHP，[YKi](https://github.com/xiyki)对该PHP版本进行了有限的更新和维护

修复了[傍晚升起的太阳](https://github.com/wuruiwm)重构的PHP版本中以下内容：
1. 解决了大部分跨域问题，将部分必须样式放在了本地
2. 将管理员密码从MD5加密修改为了Hash加密
3. 美化了管理员登陆界面
4. 其他较小的改动

目前还存在的问题：
1. 由于部分跨域问题仍未被解决，部分图标不能正常显示
2. 仅支持邀请码注册，设置为不需要邀请码时会出现错误

微软全局管理自助申请程序 支持大部分等订阅，使用方法一样

安装方法
宝塔新建PHP站点，直接丢进去；PHP版本最好在7以上
重命名`config.php.example`为`config.php`并修改配置，然后在数据库中导入sql

启用邀请码所需基础API（以下均为Microsoft Graph应用程序权限，需进行管理员授权）：
* Directory.ReadWrite.All
* Domain.ReadWrite.All
* Reports.Read.All
* RoleManagement.ReadWrite.Directory
* Sites.FullControl.All
* User.ManageIdentities.All
* User.ReadWrite.All

演示站 https://register.dzyx.de/

觉得好用的话，可以点个star鼓励下我