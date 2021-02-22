-- ----------------------------
-- Table structure for system_menu
-- ----------------------------
CREATE TABLE `system_menu`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` int(10) NULL DEFAULT NULL COMMENT '上级id',
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '名称',
  `icon` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '图标',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '地址',
  `params` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '参数',
  `sort` int(11) NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '状态',
  `create_time` int(11) UNSIGNED NULL DEFAULT NULL COMMENT '创建时间',
  `delete_time` int(11) UNSIGNED NULL DEFAULT 0 COMMENT '软删除字段',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 12 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统菜单' ROW_FORMAT = Compact;

-- ----------------------------
-- Records of system_menu
-- ----------------------------
INSERT INTO `system_menu` VALUES (1, 0, '首页', NULL, 'admin/index/main', NULL, 1000, 1, 100000, 0);
INSERT INTO `system_menu` VALUES (2, 0, '系统管理', '', '#', '', 0, 1, 10000000, 0);
INSERT INTO `system_menu` VALUES (3, 2, '权限管理', NULL, '#', NULL, 0, 1, 20000000, 0);
INSERT INTO `system_menu` VALUES (4, 3, '访问权限管理', 'layui-icon layui-icon-vercode', 'admin/rule/index', NULL, 0, 1, 30000000, 0);
INSERT INTO `system_menu` VALUES (5, 3, '系统用户管理', 'layui-icon layui-icon-username', 'admin/user/index', '', 0, 1, 1610532486, 0);
INSERT INTO `system_menu` VALUES (6, 2, '系统配置', '', '#', '', 10, 1, 1610532496, 0);
INSERT INTO `system_menu` VALUES (7, 6, '系统菜单管理', 'layui-icon layui-icon-layouts', 'admin/menu/index', NULL, 0, 1, 1610532896, 0);
INSERT INTO `system_menu` VALUES (8, 6, '系统参数配置', 'layui-icon layui-icon-set-sm', 'admin/config/info', '', 0, 1, 1610682376, 0);
INSERT INTO `system_menu` VALUES (9, 3, '系统操作日志', 'layui-icon layui-icon-form', 'admin/log/index', '', 0, 1, 1610693824, 0);
INSERT INTO `system_menu` VALUES (10, 6, '文件存储设置', 'fa fa-folder-o', 'admin/storage/index', '', 0, 1, 1610864651, 0);