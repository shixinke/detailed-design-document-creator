# detailed-design-document-creator
detailed design document creator(软件工程-详细设计文档生成)

根据数据库配置生成详细设计文档

### 1.基本用法

本工具需要PHP环境，而且安装了PDO/PDO_Mysql扩展

- (1)下载本项目源码，将config.json文件修改为自己项目的配置
- (2)运行以下命令

```php
php creator.php
```

- (3)在data/output文件夹下就会生成相应的详细文档文件
