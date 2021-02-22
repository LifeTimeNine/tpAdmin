-- ----------------------------
-- Table structure for system_user
-- ----------------------------
CREATE TABLE `system_user`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '用户账号',
  `password` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '用户密码',
  `email` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '联系邮箱',
  `mobile` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '联系手机号',
  `rid` int(10) NULL DEFAULT NULL COMMENT '角色id',
  `desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '备注',
  `last_login_time` int(11) UNSIGNED NULL DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` bigint(10) NULL DEFAULT NULL COMMENT '最后登录ip',
  `login_num` bigint(20) NULL DEFAULT 0 COMMENT '登录次数',
  `create_time` int(11) UNSIGNED NULL DEFAULT NULL COMMENT '创建时间',
  `delete_time` int(11) UNSIGNED NULL DEFAULT 0 COMMENT '删除时间',
  `status` tinyint(1) NULL DEFAULT 1 COMMENT '状态',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `system_user_username`(`username`) USING BTREE,
  INDEX `system_user_mobile`(`mobile`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '系统用户表' ROW_FORMAT = Compact;

-- ----------------------------
-- Records of system_user
-- ----------------------------
INSERT INTO `system_user` VALUES (1, 'admin', '21232f297a57a5a743894a0e4a801fc3', '2390904403@qq.com', '17319707985', 0, NULL, 1613957355, 2130706433, 48, 4294967295, 0, 1);