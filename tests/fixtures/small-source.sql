DROP TABLE IF EXISTS `fruits`;
CREATE TABLE `fruits` (
  id   INT,
  name TEXT
);

INSERT INTO `fruits` VALUES (1, 'banana');
INSERT INTO `fruits` VALUES (2, 'pear');
INSERT INTO `fruits` VALUES (3, 'apple');
INSERT INTO `fruits` VALUES (4, 'cherry');

DROP TABLE IF EXISTS `vegetables`;
CREATE TABLE `vegetables` (
  id   INT,
  name TEXT
);

INSERT INTO `vegetables` VALUES (1, 'potato');

DROP TABLE IF EXISTS `baskets`;
CREATE TABLE `baskets` (
  id    INT,
  owner TEXT
);

INSERT INTO `baskets` VALUES (1, 'Tom');
INSERT INTO `baskets` VALUES (2, 'Dick');
INSERT INTO `baskets` VALUES (3, 'Harry');

DROP TABLE IF EXISTS `fruit_x_basket`;
CREATE TABLE `fruit_x_basket` (
  id INT,
  fruit_id INT,
  basket_id INT,
  items_count INT
);

INSERT INTO `fruit_x_basket` VALUES (1, 1, 1, 3);
INSERT INTO `fruit_x_basket` VALUES (2, 3, 1, 3);

INSERT INTO `fruit_x_basket` VALUES (3, 1, 2, 2);
INSERT INTO `fruit_x_basket` VALUES (4, 2, 2, 3);

INSERT INTO `fruit_x_basket` VALUES (2, 1, 3, 4);
INSERT INTO `fruit_x_basket` VALUES (4, 4, 3, 4);
