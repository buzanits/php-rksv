-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 16, 2017 at 11:21 AM
-- Server version: 10.0.31-MariaDB-0ubuntu0.16.04.2
-- PHP Version: 7.0.22-0ubuntu0.16.04.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `phprksv`
--

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

CREATE TABLE `invoice` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `invoicedate` date NOT NULL,
  `number` varchar(255) DEFAULT NULL,
  `type` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `invoice`
--

INSERT INTO `invoice` (`id`, `title`, `invoicedate`, `number`, `type`) VALUES
(1, 'Barumsatz', '2017-10-02', 'R001', NULL),
(2, 'Bankomatzahlung', '2017-10-10', 'R002', NULL),
(3, 'Barumsatz', '2017-10-16', 'R003', NULL),
(4, 'Kreditkartenzahlung', '2017-11-03', 'R004', NULL),
(5, 'Trainingsbuchung', '2017-11-04', 'TR001', 'TR'),
(6, 'Stornorechnung', '2017-11-06', 'STORNO001', 'ST'),
(7, 'Signatureinheit ausgefallen', '2017-11-09', 'R005', 'ER'),
(8, 'Signatureinheit geht wieder', '2017-11-13', 'R006', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `invoiceline`
--

CREATE TABLE `invoiceline` (
  `id` int(11) NOT NULL,
  `invoice` int(11) NOT NULL,
  `productname` varchar(255) DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `pieceprice` decimal(10,2) NOT NULL,
  `tax` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `invoiceline`
--

INSERT INTO `invoiceline` (`id`, `invoice`, `productname`, `amount`, `pieceprice`, `tax`) VALUES
(1, 1, 'Schuhlöffel', 1, '15.00', 20),
(2, 1, 'Schuhcreme', 2, '3.50', 20),
(3, 2, 'Klopapier', 10, '1.50', 20),
(4, 3, 'Kaffee', 2, '15.75', 10),
(5, 2, 'Schokolade', 1, '3.00', 20),
(6, 3, 'Lolly', 1, '0.90', 20),
(7, 4, 'Musik-CD', 1, '20.00', 20),
(8, 5, 'Gurke', 4, '0.75', 20),
(9, 6, 'Rückgabe', 1, '-20.00', 20),
(10, 7, 'Bleistift', 1, '1.00', 20),
(11, 8, 'Türstopper', 1, '25.00', 20),
(12, 8, 'Zeitschrift', 3, '4.00', 20);

-- --------------------------------------------------------

--
-- Table structure for table `rksvreceipt`
--

CREATE TABLE `rksvreceipt` (
  `id` int(11) NOT NULL,
  `invoice` int(11) DEFAULT NULL,
  `rdate` datetime NOT NULL,
  `rnr` int(11) NOT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `certSerial` varchar(255) DEFAULT NULL,
  `chainValue` varchar(255) DEFAULT NULL,
  `dep` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


--
-- Indexes for dumped tables
--

--
-- Indexes for table `invoice`
--
ALTER TABLE `invoice`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoiceline`
--
ALTER TABLE `invoiceline`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice` (`invoice`);

--
-- Indexes for table `rksvreceipt`
--
ALTER TABLE `rksvreceipt`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice` (`invoice`),
  ADD KEY `rnr` (`rnr`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `invoice`
--
ALTER TABLE `invoice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT for table `invoiceline`
--
ALTER TABLE `invoiceline`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT for table `rksvreceipt`
--
ALTER TABLE `rksvreceipt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `invoiceline`
--
ALTER TABLE `invoiceline`
  ADD CONSTRAINT `invoiceline_ibfk_1` FOREIGN KEY (`invoice`) REFERENCES `invoice` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rksvreceipt`
--
ALTER TABLE `rksvreceipt`
  ADD CONSTRAINT `rksvreceipt_ibfk_1` FOREIGN KEY (`invoice`) REFERENCES `invoice` (`id`) ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
