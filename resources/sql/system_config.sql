-- ----------------------------
-- Table structure for system_config
-- ----------------------------
CREATE TABLE `system_config`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '配置键',
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '配置值',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `system_config_name`(`name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 26 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统配置' ROW_FORMAT = Compact;

-- ----------------------------
-- Records of system_config
-- ----------------------------
INSERT INTO `system_config` VALUES (1, 'site_name', 'TPAdmin');
INSERT INTO `system_config` VALUES (2, 'app_name', 'TPAdmin');
INSERT INTO `system_config` VALUES (3, 'app_version', 'dev');
INSERT INTO `system_config` VALUES (4, 'miitbeian', '无');
INSERT INTO `system_config` VALUES (5, 'site_copy', '无');
INSERT INTO `system_config` VALUES (6, 'storage_allowExts', 'jpg|jpeg|png|gif|zip|txt');
INSERT INTO `system_config` VALUES (7, 'storage_driver', 'Local');
INSERT INTO `system_config` VALUES (8, 'storage_local_savePath', '/uploads');
INSERT INTO `system_config` VALUES (9, 'site_icon', 'http://lifetime-tpadmin.oss-cn-beijing.aliyuncs.com/20210127/5c1cb94d00d08b8254bedf5c8d1b5fbb.png');
INSERT INTO `system_config` VALUES (10, 'storage_forbidExts', 'php|sh');
INSERT INTO `system_config` VALUES (11, 'storage_oss_isSsl', '0');
INSERT INTO `system_config` VALUES (12, 'storage_oss_bucket', 'lifetime-tpadmin');
INSERT INTO `system_config` VALUES (13, 'storage_oss_endpoint', 'oss-cn-beijing.aliyuncs.com');
INSERT INTO `system_config` VALUES (14, 'storage_oss_keyid', '');
INSERT INTO `system_config` VALUES (15, 'storage_oss_secret', '');
INSERT INTO `system_config` VALUES (16, 'storage_oss_domain', '');
INSERT INTO `system_config` VALUES (17, 'storage_prefix', '{$date1}/');
INSERT INTO `system_config` VALUES (18, 'storage_qiniu_isSsl', '0');
INSERT INTO `system_config` VALUES (19, 'storage_qiniu_bucket', 'tpadmin');
INSERT INTO `system_config` VALUES (20, 'storage_qiniu_region', '华南');
INSERT INTO `system_config` VALUES (21, 'storage_qiniu_key', '');
INSERT INTO `system_config` VALUES (22, 'storage_qiniu_secret', '');
INSERT INTO `system_config` VALUES (23, 'storage_qiniu_domain', 'storage.jealous.vip');
INSERT INTO `system_config` VALUES (24, 'storage_shard_astrict', '4');
INSERT INTO `system_config` VALUES (25, 'storage_shard_size', '4');