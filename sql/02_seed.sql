/*!50503 SET NAMES utf8mb4 */;
SET FOREIGN_KEY_CHECKS=0;
START TRANSACTION;

INSERT INTO `role` VALUES
(1,'ROLE_USER','User','2026-02-02 16:10:04','2026-02-02 16:10:04'),
(2,'ROLE_ADMIN','Admin','2026-02-02 16:10:04','2026-02-02 16:10:04'),
(3,'ROLE_EMPLOYEE','Employe','2026-02-02 16:10:04','2026-02-02 16:10:04');

INSERT INTO `user` VALUES
(1,'admin@email.com','$2y$13$.P/UEBtIB3xeQlFwVNSYgOjlVYLqgQmeQnfvn0HDGdE.Yn6R49Dhm','2026-02-09 18:01:13','2026-02-12 15:25:07','47751fefb32f89d1fbdb56dfe423168ffc8c9c46','José','0612345678','Bordeaux','France','Admin','1 rue des Gourmands',33000,1),
(2,'employee@email.com','$2y$13$5XFG9NHsMZYa/MptOd347.Pvj/QLx24Wp9crrYo8RP2B4kNJFdVVS','2026-02-13 14:48:53','2026-02-13 14:49:34','2c7f72d5950d136d7d91869d64200ef65a78bf64','Julie','0601020304','Bordeaux','France','Employé','1 rue de l\'Emploi',33000,1),
(3,'utilisateur@email.com','$2y$13$lPJWhL2AckNboiNnTnv7hOsXdmZScANHCnqt75Tc4B7cXfgp5mVIa','2026-02-13 14:55:56','2026-02-17 14:00:33','d871e07989799715d112981cedc6ec79bb3bfccf','Jean','0605060708','Bordeaux','France','Visiteur','1 rue de la Visite',33000,1);


INSERT INTO `user_role` VALUES
(1,1),(1,2),(1,3),
(2,3),
(3,1);


INSERT INTO `regime` VALUES
(1,'Classique','2026-02-02 16:10:21','2026-02-02 16:10:21'),
(2,'Vegetarien','2026-02-02 16:10:21','2026-02-02 16:10:21'),
(3,'Testeurs','2026-02-16 14:27:51',NULL);

INSERT INTO `theme` VALUES
(1,'Les Festifs','2026-02-02 16:10:44','2026-02-02 16:10:44'),
(2,'Les Quotidiens','2026-02-02 16:10:44','2026-02-02 16:10:44'),
(3,'Mariage','2026-02-13 16:29:18',NULL);


INSERT INTO `allergene` VALUES
(1,'Moutarde','2026-02-02 16:09:24','2026-02-02 16:09:24'),
(2,'Oeufs','2026-02-02 16:09:24','2026-02-02 16:09:24'),
(3,'Gluten','2026-02-02 16:09:24','2026-02-02 16:09:24'),
(4,'Fruits a Coques','2026-02-02 16:09:24','2026-02-02 16:09:24'),
(5,'Arachides','2026-02-05 14:27:09','2026-02-05 14:27:09'),
(6,'Cacao','2026-02-05 14:27:28','2026-02-05 14:27:28'),
(7,'Lactose','2026-02-05 14:27:28','2026-02-05 14:27:28');

INSERT INTO `plat` VALUES
(1,'Salade Festive','entree','plat_1_95374f93bc7c.jpg','2026-02-02 16:08:49','2026-02-02 16:08:49'),
(2,'Dinde de Noël','plat','plat_2_503c3f2c6ab2.jpg','2026-02-02 16:08:49','2026-02-02 16:08:49'),
(3,'Buche 3 chocolats','dessert','plat_3_1815c4c250d3.jpg','2026-02-02 16:08:49','2026-02-02 16:08:49'),
(4,'Lapin a la moutarde','plat','plat_4_58bbde7eb623.jpg','2026-02-02 16:08:49','2026-02-02 16:08:49'),
(5,'Gateau au Oeufs de Paques','dessert','plat_5_6d099c06362b.jpg','2026-02-02 16:08:49','2026-02-02 16:08:49'),
(6,'Salade Verte','entree','plat_6_93ba761e5a68.jpg','2026-02-05 14:23:35','2026-02-05 14:23:35'),
(7,'Poulet Roti','plat','plat_7_19ef55bd5e78.jpg','2026-02-05 14:23:35','2026-02-05 14:23:35'),
(8,'Frites','plat','plat_8_bba4d6300bdb.jpg','2026-02-05 14:23:35','2026-02-05 14:23:35'),
(9,'Mousse au Chocolat','dessert','plat_9_1c54b6aece7c.jpg','2026-02-05 14:23:35','2026-02-05 14:23:35'),
(10,'Foie Gras ','entree','plat_10_681870a3d292.jpg','2026-02-11 18:51:41','2026-02-11 18:51:41'),
(11,'Salade Dansante','entree','plat_11_fb416bb3ebd4.png','2026-02-11 18:51:41','2026-02-11 18:51:41'),
(12,'Magret de Canard Sauce Morille','plat','plat_12_54cc000205e4.webp','2026-02-11 18:51:41','2026-02-11 18:51:41'),
(13,'Riz Sauvage ','plat','plat_13_b117396caee1.webp','2026-02-11 18:51:41','2026-02-11 18:51:41'),
(14,'La Piece Montée ','dessert','plat_14_4172541b049b.jpg','2026-02-11 18:51:41','2026-02-11 18:51:41'),
(15,'Pommes Duchesses','plat','plat_15_70d84cff8e2a.jpg','2026-02-16 13:12:50','2026-02-16 14:14:09');

INSERT INTO `plat_allergene` VALUES
(1,1),(2,4),(3,4),(3,6),(4,1),(5,2),(5,3),(6,1),(8,5),(9,2),(9,6),(9,7),(15,4),(15,5);

INSERT INTO `menu` VALUES
(1,1,1,'Noel 2026',4,20.00,'Un Menu Festif pour se régaler en cette belle fin d\'année.',12,'2026-02-02 16:05:44','2026-02-13 19:34:38',0),
(2,1,1,'Paques 2026',4,16.50,'Un menu de Paques qui réveille les cloches.',12,'2026-02-02 16:06:52','2026-02-13 18:19:50',0),
(3,1,2,'Le Poulet Roti',2,10.50,'Un poulet roti, des patates et beaucoup d\'amour. A déguster tout les jours, ou le dimanche devant Texas Walker...',10,'2026-02-05 14:19:27','2026-02-13 18:29:20',0),
(4,1,3,'Menu Mariage',10,35.00,'Menu complet pour un mariage réussi',30,'2026-02-11 18:47:28','2026-02-16 11:11:41',1),
(5,2,3,'Mon Mariage Végé',1,25.50,'Un Menu élégant et végétarien pour satisfaire tout les gouts',14,'2026-02-13 16:35:55','2026-02-18 17:05:10',0);

INSERT INTO `menu_plat` VALUES
(1,1),(1,2),(1,3),(1,15),
(2,1),(2,4),(2,5),
(3,6),(3,7),(3,8),(3,9),
(4,10),(4,11),(4,12),(4,13),(4,14);

INSERT INTO `commande` VALUES

(19,3,4,'26021118553462','2026-02-15 18:55:34','2026-02-26','18:55:00',459.00,10,0.00,459.00,'retour_materiel',NULL,1,'1 rue de la visite',NULL,NULL,NULL,'2026-03-06 19:12:04',NULL),
(20,3,3,'26021119200863','2026-02-18 19:20:08','2026-02-19','20:20:00',21.00,2,0.00,21.00,'annulee',NULL,NULL,'1 rue de la visite','2026-02-17 19:20:57','mail','Client injoignable / rupture stock / report impossible',NULL,NULL),
(21,3,1,'26021119340588','2026-02-17 19:34:05','2026-02-20','20:00:00',80.00,4,0.00,80.00,'refusee',NULL,NULL,'1 rue de la visite','2026-02-1 19:35:33','gsm','Rupture de stock',NULL,NULL),
(24,3,1,'26021614335755','2026-02-16 14:33:57','2026-02-26','19:30:00',80.00,4,0.00,80.00,'terminee',NULL,NULL,'1 rue de la visite',NULL,NULL,NULL,NULL,'2026-02-17 12:50:02'),
(28,3,1,'26021715263957','2026-02-10 15:26:39','2026-02-23','20:30:00',80.00,4,0.00,80.00,'terminee',NULL,NULL,'1 rue de la visite',NULL,NULL,NULL,NULL,'2026-02-17 15:26:59'),
(30,3,3,'26021715341024','2026-02-09 15:34:10','2026-02-19','20:34:00',21.00,2,0.00,21.00,'terminee',NULL,NULL,'1 rue de la visite',NULL,NULL,NULL,NULL,'2026-02-17 15:42:31'),
(34,3,2,'26030110150001','2026-03-01 10:15:00','2026-03-10','19:30:00',66.00,4,0.00,66.00,'en_attente',NULL,NULL,'1 rue de la visite',NULL,NULL,NULL,NULL,NULL),
(35,3,3,'26030111180002','2026-03-01 11:18:00','2026-03-12','20:00:00',21.00,2,0.00,21.00,'preparation',NULL,NULL,'1 rue de la visite',NULL,NULL,NULL,NULL,NULL);

INSERT INTO `avis` VALUES
(1,3,5,'Excellent service et plats délicieux','accepte','2026-02-18 12:18:42','2026-02-18 16:01:42',24),
(2,3,4,'Avec mes proches on s\'est régaler avec le Menu de Noël.','refuse','2026-02-18 13:25:57','2026-02-18 16:02:26',28),
(3,3,5,'Poulet Roti Succulent !','accepte','2026-02-18 13:29:49','2026-02-18 19:23:07',30);


COMMIT;
SET FOREIGN_KEY_CHECKS=1;
