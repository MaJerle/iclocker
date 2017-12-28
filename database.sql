-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Feb 17, 2017 at 08:21 AM
-- Server version: 10.1.8-MariaDB
-- PHP Version: 5.6.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `components`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `collection_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `deleted` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `description` text,
  `elements_count` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `categories_properties`
--

CREATE TABLE `categories_properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `collection_id` int(11) NOT NULL DEFAULT '0',
  `category_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `deleted` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `collections`
--

CREATE TABLE `collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(45) NOT NULL,
  `description` text,
  `deleted` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `elements_count` int(11) NOT NULL DEFAULT '0',
  `categories_count` int(11) NOT NULL DEFAULT '0',
  `properties_count` int(11) NOT NULL DEFAULT '0',
  `elementorders_count` int(11) NOT NULL DEFAULT '0',
  `products_count` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `parent_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `foreign_id` int(11) NOT NULL,
  `collection_id` int(11) NOT NULL,
  `model` int(11) NOT NULL,
  `deleted` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `elementorders`
--

CREATE TABLE `elementorders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `status` int(11) DEFAULT '1',
  `name` varchar(45) DEFAULT NULL,
  `datecreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dateordered` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `collection_id` int(11) NOT NULL,
  `synced` int(11) NOT NULL DEFAULT '0',
  `quantity_type` int(11) NOT NULL DEFAULT '0',
  `deleted` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `orderelements_count` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `elementorders_properties`
--

CREATE TABLE `elementorders_properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `collection_id` int(11) NOT NULL DEFAULT '0',
  `elementorder_id` int(11) NOT NULL DEFAULT '0',
  `property_id` int(11) NOT NULL DEFAULT '0',
  `deleted` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `elements`
--

CREATE TABLE `elements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `category_id` int(11) NOT NULL DEFAULT '0',
  `collection_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(45) NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT '0',
  `warning_quantity` int(11) NOT NULL DEFAULT '0',
  `deleted` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `copy_id` int(11) NOT NULL DEFAULT '0',
  `revision_reason` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `elements_products`
--

CREATE TABLE `elements_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `collection_id` int(11) NOT NULL DEFAULT '0',
  `product_id` int(11) NOT NULL DEFAULT '0',
  `element_id` int(11) NOT NULL DEFAULT '0',
  `element_count` int(11) NOT NULL DEFAULT '9',
  `deleted` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `elements_properties`
--

CREATE TABLE `elements_properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `collection_id` int(11) NOT NULL DEFAULT '0',
  `element_id` int(11) NOT NULL DEFAULT '0',
  `property_id` int(11) NOT NULL DEFAULT '0',
  `property_value` varchar(255) DEFAULT NULL,
  `deleted` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '0',
  `model` varchar(32) NOT NULL,
  `foreign_id` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `mime` varchar(32) NOT NULL,
  `size` int(11) NOT NULL DEFAULT '0',
  `deleted` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `servername` varchar(48) NOT NULL,
  `md5hash` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `orderelements`
--

CREATE TABLE `orderelements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `number` varchar(20) NOT NULL,
  `desiredquantity` int(11) NOT NULL DEFAULT '0',
  `minquantity` int(11) NOT NULL DEFAULT '0',
  `ordered` int(11) NOT NULL DEFAULT '0',
  `purpose` varchar(100) DEFAULT NULL,
  `description` varchar(200) DEFAULT NULL,
  `elementorder_id` int(11) NOT NULL DEFAULT '0',
  `element_id` int(11) NOT NULL DEFAULT '0',
  `collection_id` int(11) NOT NULL,
  `deleted` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `collection_id` int(11) NOT NULL,
  `name` varchar(45) DEFAULT NULL,
  `description` text,
  `quantity` int(11) NOT NULL DEFAULT '0',
  `deleted` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `products_subproducts`
--

CREATE TABLE `products_subproducts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `collection_id` int(11) NOT NULL DEFAULT '0',
  `product_id` int(11) NOT NULL DEFAULT '0',
  `subproduct_id` int(11) NOT NULL DEFAULT '0',
  `subproduct_count` int(11) NOT NULL DEFAULT '0',
  `deleted` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `collection_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `data_type` int(11) NOT NULL DEFAULT '0',
  `unit` varchar(45) DEFAULT NULL,
  `deleted` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `propertychoices_count` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `propertychoices`
--

CREATE TABLE `propertychoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `property_id` int(11) NOT NULL DEFAULT '0',
  `choice` varchar(45) DEFAULT NULL,
  `collection_id` int(11) NOT NULL DEFAULT '0',
  `deleted` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tokens`
--

CREATE TABLE `tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `code` varchar(64) NOT NULL,
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `username` varchar(45) NOT NULL,
  `password` varchar(64) NOT NULL,
  `access_group` int(11) NOT NULL DEFAULT '0',
  `first_name` varchar(45) DEFAULT NULL,
  `last_name` varchar(45) DEFAULT NULL,
  `deleted` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lock_code` varchar(10) NOT NULL DEFAULT '1234',
  `dynamic_token` int(11) NOT NULL DEFAULT '0',
  `image` varchar(70) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `usersettings`
--

CREATE TABLE `usersettings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `setting` varchar(32) NOT NULL,
  `value` varchar(32) NOT NULL,
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users_collections`
--

CREATE TABLE `users_collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `collection_id` int(11) NOT NULL DEFAULT '0',
  `deleted` int(11) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `usertokens`
--

CREATE TABLE `usertokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revision_id` int(11) NOT NULL DEFAULT '0',
  `token` varchar(64) DEFAULT NULL,
  `dynamic_token` varchar(64) NOT NULL,
  `validto` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int(11) NOT NULL DEFAULT '0',
  `modified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_by` int(11) NOT NULL DEFAULT '0',
  `deleted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `ip` varchar(20) DEFAULT NULL,
  `deleted` int(11) NOT NULL DEFAULT '0',
  `next_token` varchar(48) DEFAULT NULL,
  `invalidated` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`,`collection_id`),
  ADD KEY `fk_categories_collections1_idx` (`collection_id`);

--
-- Indexes for table `categories_properties`
--
ALTER TABLE `categories_properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_categories_has_properties_properties1_idx` (`property_id`),
  ADD KEY `fk_categories_has_properties_categories_idx` (`category_id`);

--
-- Indexes for table `collections`
--
ALTER TABLE `collections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `elementorders`
--
ALTER TABLE `elementorders`
  ADD PRIMARY KEY (`id`,`collection_id`),
  ADD KEY `fk_orders_collections1_idxx` (`collection_id`);

--
-- Indexes for table `elementorders_properties`
--
ALTER TABLE `elementorders_properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `elementorder_id` (`elementorder_id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `elements`
--
ALTER TABLE `elements`
  ADD PRIMARY KEY (`id`,`category_id`,`collection_id`),
  ADD KEY `fk_elements_categories1_idx` (`category_id`),
  ADD KEY `fk_elements_collections1_idx` (`collection_id`);

--
-- Indexes for table `elements_products`
--
ALTER TABLE `elements_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_has_elements_elements1` (`element_id`),
  ADD KEY `product_id` (`product_id`,`element_id`);

--
-- Indexes for table `elements_properties`
--
ALTER TABLE `elements_properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_elements_has_properties_properties1_idx` (`property_id`),
  ADD KEY `fk_elements_has_properties_elements1_idx` (`element_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orderelements`
--
ALTER TABLE `orderelements`
  ADD PRIMARY KEY (`id`,`elementorder_id`,`collection_id`),
  ADD KEY `fk_orderelements_orders1_idx` (`elementorder_id`),
  ADD KEY `fk_orderelements_collections1_idx` (`collection_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`,`collection_id`),
  ADD KEY `fk_products_collections1_idx` (`collection_id`);

--
-- Indexes for table `products_subproducts`
--
ALTER TABLE `products_subproducts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`,`subproduct_id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`,`collection_id`),
  ADD KEY `fk_properties_collections1_idx` (`collection_id`);

--
-- Indexes for table `propertychoices`
--
ALTER TABLE `propertychoices`
  ADD PRIMARY KEY (`id`,`property_id`,`collection_id`),
  ADD KEY `fk_propertychoices_properties1_idx` (`property_id`),
  ADD KEY `fk_propertychoices_collections1_idx` (`collection_id`);

--
-- Indexes for table `tokens`
--
ALTER TABLE `tokens`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `usersettings`
--
ALTER TABLE `usersettings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users_collections`
--
ALTER TABLE `users_collections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_table1_has_collections_collections1_idx` (`collection_id`),
  ADD KEY `fk_table1_has_collections_table11_idx` (`user_id`);

--
-- Indexes for table `usertokens`
--
ALTER TABLE `usertokens`
  ADD PRIMARY KEY (`id`,`user_id`),
  ADD KEY `fk_usertokens_users1_idx` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1426;
--
-- AUTO_INCREMENT for table `categories_properties`
--
ALTER TABLE `categories_properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2364;
--
-- AUTO_INCREMENT for table `collections`
--
ALTER TABLE `collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;
--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;
--
-- AUTO_INCREMENT for table `elementorders`
--
ALTER TABLE `elementorders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;
--
-- AUTO_INCREMENT for table `elementorders_properties`
--
ALTER TABLE `elementorders_properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;
--
-- AUTO_INCREMENT for table `elements`
--
ALTER TABLE `elements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4176;
--
-- AUTO_INCREMENT for table `elements_products`
--
ALTER TABLE `elements_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10329;
--
-- AUTO_INCREMENT for table `elements_properties`
--
ALTER TABLE `elements_properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38317;
--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1500;
--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;
--
-- AUTO_INCREMENT for table `orderelements`
--
ALTER TABLE `orderelements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;
--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=209;
--
-- AUTO_INCREMENT for table `products_subproducts`
--
ALTER TABLE `products_subproducts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=222;
--
-- AUTO_INCREMENT for table `propertychoices`
--
ALTER TABLE `propertychoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=272;
--
-- AUTO_INCREMENT for table `tokens`
--
ALTER TABLE `tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;
--
-- AUTO_INCREMENT for table `usersettings`
--
ALTER TABLE `usersettings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `users_collections`
--
ALTER TABLE `users_collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;
--
-- AUTO_INCREMENT for table `usertokens`
--
ALTER TABLE `usertokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=783;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
