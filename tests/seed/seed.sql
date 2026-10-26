-- GENERATED FILE — do not edit.
-- Regenerate with: php tests/tools/generate-seed.php (source: tests/seed/plaintexts.php)
-- Vault Account credentials use the legacy CI3 encryption format (see file header
-- of tests/tools/generate-seed.php), so the legacy oracle decrypts them too.

START TRANSACTION;
SET NAMES utf8mb4;

INSERT INTO `access_rights` (`access_level`, `status_access`) VALUES
(1, 'admin'),
(2, 'user');

INSERT INTO `feature` (`id_feature`, `feature_name`, `url`) VALUES
(1, 'user', '#'),
(2, 'account', '#');

INSERT INTO `access_list` (`access_level`, `id_feature`) VALUES
(1, 1),
(2, 2);

INSERT INTO `account_media` (`id_media`, `logo`, `name`) VALUES
(1, 'fab fa-instagram', 'Instagram'),
(2, 'fab fa-twitter', 'Twitter'),
(3, 'fab fa-facebook-f', 'Facebook'),
(4, 'fab fa-google', 'Google'),
(5, 'fab fa-medium', 'Medium'),
(6, 'fab fa-quora', 'Quora'),
(7, 'fab fa-trello', 'Trello'),
(8, 'fab fa-line', 'Line'),
(9, 'fab fa-cc-visa', 'Visa'),
(10, 'fab fa-cc-mastercard', 'Mastercard'),
(11, 'fab fa-cc-amex', 'American Express'),
(12, 'fab fa-amazon', 'Amazon'),
(13, 'fab fa-apple', 'Apple'),
(14, 'fab fa-steam', 'Steam');

INSERT INTO `user` (`id_user`, `email`, `name`, `password`, `registered`, `access_level`) VALUES
(1, 'admin@email.com', 'Admin', '$2y$12$GEVY4VmcO0XMwhUC1tFqhOmiaBNAXEKsY.WqrTFIG8rx94e/GjuUe', 1, 1),
(2, 'jack@email.com', 'Jack', '$2y$12$Tfp2pj2CF.wu84OJCESL/uzl6WU.A7uZ6rvf/pS1XvtlpTUTwllom', 1, 2),
(3, 'ghost@email.com', 'Ghost', '$2y$12$zSRvbLy7WPU9c5t2OjtE2eQLmGxTtZy6fwA3OqCOKLhTTYRe50NIm', 0, 2);

INSERT INTO `account` (`id_account`, `id_user`, `username_email`, `password`, `id_media`, `key_str`) VALUES
(1, 2, '92d474bbef0fe99f9d591f6fc7631448d0d237f6a244a92f8d17b227a745988dec5e3ce6ba9cf35ca4ec9025baa55d5272f9dc5acf4004d53a18bae0dcb2e2b9LaFVFEm184Evq+HK6hyWW+yolb95jq97dhTyeRO4AyjF8+SnkNyszTZXMcI=', '0101233346a18db44c20e088a1f6442939187d931f8e5cb29badb4ebfc6fb5d1a2cee3431a03e906361dcde9360e68dbd214d2b532ac6da2d84e8018014322f0Gj+yQOnSPVPz7w5a86dNp7MFsYgwcValump9paREKg==', 1, '1a26fa2004c2fe3c8ea06381360ad4b2cfdb237b3e0c46fdedd791ac65d04b35'),
(2, 2, 'b05c4d253a9961c0631318f587fbb4162c94d7523d32bfac7bef95babcbeaf8eae165987a4e44cfec31127f66698231d35b27ce0d44f421b19f8d09d86785553yOlY3pJpJ1x7j2va1eO1eRH6gKVNA16kYQ==', '699b253723a65557011f1f478ce97c242b021e5dc2220ec6a27a6b9088e851038cd56d6daecc5a820ad2c54835e9a5d0593f286e8cbd04fee5eb0a6b98cc2d43nlqg+u6/JANfYKI6KFlSDY8bryNO/17AsIAC+tD9Xw==', 2, 'e26ecd5192398889b33ab9ea28d9449fdaa1025ec3486b7744422f1972fc258b'),
(3, 2, 'e2a1ee296452c32ddd9949b702c9881d7a97a60b8eb11cc3e2a8ece4e7996f21c14e05b93da04e88dae7575ed09ad1172960a7024d11ec24d069e34441aa01deUq7F5X5aF0W8woS/89h/HfNMpOedmZU6koMuxp8gWvzzcmA=', '09f70c81ea36b2819acd54cb92131051574a90ff35102878d06df94d4dbf9ef6a79c0fe201b6e53a075f3daba9f02cd2a737de0bd388bbc7d0ba8f59fa0a2b94l5IlA7lxq8nVv/+jyUTv+8osYNRaMv/q8dOtIB2HXw==', 12, 'c717d1b46d52601f169577afb9c1a7c83d1dad1de77cb418792d2b8f4b678bab'),
(4, 2, '72d9396231246fbc02b8515e21534b89650c0b6f12e5d4bb0a8d55bef732f648998a6b8c26a83c6f63f1e4c6be88080620ee145d1cd6d8bb0caa1e265f26b247Ofy3+bwLUQ/sQ+yhHkVmIMM2sXqH/h02aA==', '68848e1fc4f83d41460b9efd1370c5fb4cbdc4bdff47fec889df26524d3b4196508384dcabf7c4042df7f9f586822bbe60099f28d3329c2a1fe89a982e9bb6f58qkE3Poq7h/JkFA8Xidktzc2H2Es7H1GK+pwfQ==', 14, '0177ca4324f49dfe8e877cc84bb9e9ce789d4dde15e1a9308e78e645da4337bb');

COMMIT;
