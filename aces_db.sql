-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 07, 2026 at 02:21 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `aces_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(200) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `name`, `role_id`) VALUES
(1, 'admin', '$2y$10$miFDXGsM0a7fkvK1ZSu9HuEZ6VMsQ74naJOy9U7Ep.UABOHBbJ0je', 'asianisnotnice', 1);

-- --------------------------------------------------------

--
-- Table structure for table `loan`
--

CREATE TABLE `loan` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `principal_amount` decimal(15,2) DEFAULT NULL,
  `interest_rate` decimal(5,2) DEFAULT NULL,
  `loan_status` varchar(20) DEFAULT 'pending',
  `due_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `membership_type` varchar(50) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `prefix` varchar(20) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `death_date` date DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `is_mgs` tinyint(1) DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `telephone_number` varchar(20) DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('Active','Deceased','Delisted','On-Hold','Overdue','Under Litigation') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `member_id`, `membership_type`, `username`, `password`, `first_name`, `middle_name`, `last_name`, `email`, `profile_picture`, `prefix`, `suffix`, `birthdate`, `death_date`, `approval_date`, `balance`, `is_mgs`, `remarks`, `role_id`, `phone_number`, `telephone_number`, `civil_status`, `address`, `status`) VALUES
(1, 1, 'Regular', 'mary1', '$2y$10$yL5CKPmc4vAP2cL4achM6.0PC78vz6n1NceUqTS.pD3.RcDy0N0F2', 'Mary', 'Gabriel', 'Davis', 'mary.davis1@example.com', 'default.png', '', '', '1971-03-28', NULL, '2026-03-05 09:45:40', 40670.00, 1, 'System generated member profile.', 2, '09827066064', '82093975', 'Single', '1 Main St, Barangay 7, Metro Manila', 'Overdue'),
(2, 2, 'Regular', 'james2', '$2y$10$idPvlbY2P1TzuGi58l6Gl.1M/dsVVa/cQBpg7YocGOPJheZO4DqYu', 'James', 'Catherine', 'Williams', 'james.williams2@example.com', 'default.png', '', '', '1982-11-24', NULL, '2026-03-05 09:45:40', 47551.00, 1, 'System generated member profile.', 2, '09277661896', '82636899', 'Widowed', '2 Main St, Barangay 14, Metro Manila', 'Overdue'),
(3, 3, 'Regular', 'mary3', '$2y$10$GL4rkxuWXcmuXnw51m/oTOUfhnQM09aFwIUcB0WKf0cet.8Z4E6A6', 'Mary', 'Gabriel', 'Brown', 'mary.brown3@example.com', 'default.png', '', '', '1985-10-01', NULL, '2026-03-05 09:45:41', 26744.00, 1, 'System generated member profile.', 2, '09323926876', '82706147', 'Single', '3 Main St, Barangay 17, Metro Manila', 'Under Litigation'),
(4, 4, 'Regular', 'mary4', '$2y$10$qj8kJd9n8v7ovFil.pwZL.Vx1emJuG1GAHSn5csqr4D0qcrsMGEe2', 'Mary', 'Catherine', 'Williams', 'mary.williams4@example.com', 'default.png', '', '', '2003-08-02', NULL, '2026-03-05 09:45:41', 3663.00, 1, 'System generated member profile.', 2, '09159676208', '82766341', 'Widowed', '4 Main St, Barangay 20, Metro Manila', 'Active'),
(5, 5, 'Regular', 'robert5', '$2y$10$thLCcT85h5g6ke63sTDrle6rZFl/z1NDge71HyQ1wPP9bEBwKVvjO', 'Robert', 'Gabriel', 'Williams', 'robert.williams5@example.com', 'default.png', '', '', '1996-08-09', NULL, '2026-03-05 09:45:41', 47472.00, 1, 'System generated member profile.', 2, '09542049364', '84130167', 'Married', '5 Main St, Barangay 4, Metro Manila', 'Delisted'),
(6, 6, 'Regular', 'mary6', '$2y$10$w2Ie/frOppKLF/6S6bL8P.NOghmg3jch0YXP1a6qEYtZvMC2FLYTC', 'Mary', 'Fernando', 'Miller', 'mary.miller6@example.com', 'default.png', '', '', '1971-10-26', NULL, '2026-03-05 09:45:41', 44068.00, 0, 'System generated member profile.', 2, '09802444721', '84109111', 'Single', '6 Main St, Barangay 19, Metro Manila', 'Overdue'),
(7, 7, 'Regular', 'mary7', '$2y$10$xXCFv47mWTw1UCLpIVdk2OyjcL1BZrdoOyKMtj2r1QlArTeJcOOei', 'Mary', 'Gabriel', 'Garcia', 'mary.garcia7@example.com', 'default.png', '', '', '1993-05-11', NULL, '2026-03-05 09:45:41', 35959.00, 1, 'System generated member profile.', 2, '09842935535', '88884965', 'Widowed', '7 Main St, Barangay 16, Metro Manila', 'Under Litigation'),
(8, 8, 'Regular', 'robert8', '$2y$10$MIOICDBqYYC3xcOfDOwsCOHiCTfcQ0jHV8ELV8ImRqYHq.SE.bH1q', 'Robert', 'Evangeline', 'Miller', 'robert.miller8@example.com', 'default.png', '', '', '1977-01-05', NULL, '2026-03-05 09:45:41', 12776.00, 0, 'System generated member profile.', 2, '09558476231', '82236842', 'Separated', '8 Main St, Barangay 14, Metro Manila', 'On-Hold'),
(9, 9, 'Regular', 'robert9', '$2y$10$6ycJgPQgNj2/V.H5h9R2geHKFNL3IMjdrTenSVsVtIT5INZDk6ZSW', 'Robert', 'Alexander', 'Jones', 'robert.jones9@example.com', 'default.png', '', '', '1979-01-15', NULL, '2026-03-05 09:45:41', 28902.00, 1, 'System generated member profile.', 2, '09100426474', '84506567', 'Separated', '9 Main St, Barangay 8, Metro Manila', 'Overdue'),
(10, 10, 'Regular', 'robert10', '$2y$10$9yqVwZDS51iaOb4/yIATJO7rQfRch0BzjXb1xe8w1UJQl890tgbB6', 'Robert', 'Bernardo', 'Davis', 'robert.davis10@example.com', 'default.png', 'Rev.', '', '2002-05-21', NULL, '2026-03-05 09:45:41', 18184.00, 0, 'System generated member profile.', 2, '09119510187', '84425606', 'Separated', '10 Main St, Barangay 7, Metro Manila', 'Under Litigation'),
(11, 11, 'Regular', 'james11', '$2y$10$wGon8bIcE.RzEq5/HtgXJeOXtu.W0wSuecxAz7TT2JIlx8Yf4Fsou', 'James', 'Fernando', 'Williams', 'james.williams11@example.com', 'default.png', '', '', '1987-11-21', NULL, '2026-03-05 09:45:41', 14397.00, 1, 'System generated member profile.', 2, '09342284526', '86871233', 'Separated', '11 Main St, Barangay 13, Metro Manila', 'Deceased'),
(12, 12, 'Regular', 'robert12', '$2y$10$NLyFwq3l6ZaO4/EvFnQ/muianLE3WWFW/jmHSU24j4wm39imUdYF6', 'Robert', 'Gabriel', 'Jones', 'robert.jones12@example.com', 'default.png', '', '', '2003-08-18', NULL, '2026-03-05 09:45:41', 35231.00, 0, 'System generated member profile.', 2, '09700912984', '85983280', 'Single', '12 Main St, Barangay 4, Metro Manila', 'Overdue'),
(13, 13, 'Regular', 'robert13', '$2y$10$tUpDf/Li0e9TyMtT2vMP5uCVsD0RQYlcz1WRcOfhiBLxICmAbrT5e', 'Robert', 'Bernardo', 'Williams', 'robert.williams13@example.com', 'default.png', '', '', '1973-03-20', NULL, '2026-03-05 09:45:41', 11457.00, 1, 'System generated member profile.', 2, '09176913475', '81437175', 'Separated', '13 Main St, Barangay 6, Metro Manila', 'Active'),
(14, 14, 'Regular', 'robert14', '$2y$10$ww1MQeGvFy/pZMhpAhgdAOgcloSHbF9XprxVPIGFpdoemFdbjupNm', 'Robert', 'Gabriel', 'Johnson', 'robert.johnson14@example.com', 'default.png', '', '', '1986-08-24', NULL, '2026-03-05 09:45:41', 39383.00, 1, 'System generated member profile.', 2, '09522402913', '87948186', 'Widowed', '14 Main St, Barangay 7, Metro Manila', 'Active'),
(15, 15, 'Regular', 'john15', '$2y$10$KSt7XQntlnhYSOEStT7ewuoRaPZID1oGz77NcMrxrBacDSf5HHZOm', 'John', 'Catherine', 'Smith', 'john.smith15@example.com', 'default.png', '', 'Jr.', '1987-07-08', NULL, '2026-03-05 09:45:41', 31126.00, 1, 'System generated member profile.', 2, '09454661010', '88961935', 'Single', '15 Main St, Barangay 3, Metro Manila', 'Under Litigation'),
(16, 16, 'Regular', 'james16', '$2y$10$sKfd99/vAQ233A45UhKSCeLJWfCdw.QA//A60CWFcWQSG3G3q.9Ca', 'James', 'Dominic', 'Williams', 'james.williams16@example.com', 'default.png', '', '', '2001-10-27', NULL, '2026-03-05 09:45:41', 17648.00, 0, 'System generated member profile.', 2, '09308916471', '89591524', 'Widowed', '16 Main St, Barangay 16, Metro Manila', 'Overdue'),
(17, 17, 'Regular', 'john17', '$2y$10$FnhziqvC7Arlc9xZOQEznuogXUV21dCRIl1LzJ.rOkV1KKw6Ivb9y', 'John', 'Fernando', 'Smith', 'john.smith17@example.com', 'default.png', '', '', '1976-12-16', NULL, '2026-03-05 09:45:42', 19363.00, 1, 'System generated member profile.', 2, '09133978573', '83161372', 'Married', '17 Main St, Barangay 17, Metro Manila', 'Active'),
(18, 18, 'Regular', 'michael18', '$2y$10$WOk5hG5xCiTtnM9b07e5B.1QvP7VWPv4T4RqzVishqkMjGIxXxwmi', 'Michael', 'Alexander', 'Brown', 'michael.brown18@example.com', 'default.png', '', '', '1985-06-10', NULL, '2026-03-05 09:45:42', 23096.00, 0, 'System generated member profile.', 2, '09984005241', '85419106', 'Married', '18 Main St, Barangay 6, Metro Manila', 'Deceased'),
(19, 19, 'Regular', 'patricia19', '$2y$10$ZZmQW0YdFXHXcXFPKhB/He36KQsYQqGxVyJoymYfr/AXY15QiGq1C', 'Patricia', 'Gabriel', 'Williams', 'patricia.williams19@example.com', 'default.png', '', '', '1979-10-28', NULL, '2026-03-05 09:45:42', 11359.00, 1, 'System generated member profile.', 2, '09619926455', '88066591', 'Married', '19 Main St, Barangay 10, Metro Manila', 'Under Litigation'),
(20, 20, 'Regular', 'jennifer20', '$2y$10$R73nB7ejXViVra62MMWlzOW/3h4jTgv8KsCv1dmG0V724fEb9lE4W', 'Jennifer', 'Fernando', 'Davis', 'jennifer.davis20@example.com', 'default.png', 'Rev.', '', '1999-12-06', NULL, '2026-03-05 09:45:42', 40580.00, 0, 'System generated member profile.', 2, '09647578250', '85710645', 'Widowed', '20 Main St, Barangay 7, Metro Manila', 'On-Hold'),
(21, 21, 'Regular', 'james21', '$2y$10$cdpsg2om3in3M4SqmvAKOOlsNYlbNZlCaqor2UiQDZrcTGwuXjOnu', 'James', 'Alexander', 'Davis', 'james.davis21@example.com', 'default.png', '', '', '1995-07-23', NULL, '2026-03-05 09:45:42', 8796.00, 0, 'System generated member profile.', 2, '09303115077', '87431633', 'Married', '21 Main St, Barangay 19, Metro Manila', 'Delisted'),
(22, 22, 'Regular', 'robert22', '$2y$10$2yFQlojEOhmpyEEeOGJF9Oc./XfFkmQoRiD9SHiUcjdW2Axo2SOqy', 'Robert', 'Gabriel', 'Davis', 'robert.davis22@example.com', 'default.png', '', '', '1981-10-05', NULL, '2026-03-05 09:45:42', 25217.00, 1, 'System generated member profile.', 2, '09487555171', '86784557', 'Widowed', '22 Main St, Barangay 18, Metro Manila', 'Deceased'),
(23, 23, 'Regular', 'michael23', '$2y$10$xtZncc4wXfCnbSuoU36BCOfWNPtIxCetMsPcuU0Vpls9pJV50iwXq', 'Michael', 'Evangeline', 'Brown', 'michael.brown23@example.com', 'default.png', '', '', '1993-04-15', NULL, '2026-03-05 09:45:42', 46085.00, 1, 'System generated member profile.', 2, '09483363652', '82037895', 'Married', '23 Main St, Barangay 16, Metro Manila', 'Overdue'),
(24, 24, 'Regular', 'michael24', '$2y$10$VnY8BexkDLlwZrjk9.UrJuzzshgWziqMjmDNeV2tmamyFd94cdhUa', 'Michael', 'Bernardo', 'Johnson', 'michael.johnson24@example.com', 'default.png', '', '', '1996-08-22', NULL, '2026-03-05 09:45:42', 40239.00, 1, 'System generated member profile.', 2, '09369129615', '88372030', 'Single', '24 Main St, Barangay 16, Metro Manila', 'Under Litigation'),
(25, 25, 'Regular', 'robert25', '$2y$10$S8JB83oyJObsj7R2vVuzl.dR5scqfWVUBw49jXCmvmQPLT8TMSrja', 'Robert', 'Gabriel', 'Johnson', 'robert.johnson25@example.com', 'default.png', '', '', '1978-09-11', NULL, '2026-03-05 09:45:42', 31184.00, 1, 'System generated member profile.', 2, '09699525701', '88807460', 'Separated', '25 Main St, Barangay 13, Metro Manila', 'Overdue'),
(26, 26, 'Regular', 'james26', '$2y$10$6ESME8su/DOHua0zUURpUeQPcNo1OFHfk2aGPk/FQ6ECxhWRRDG4a', 'James', 'Catherine', 'Davis', 'james.davis26@example.com', 'default.png', '', '', '1971-11-20', NULL, '2026-03-05 09:45:42', 11736.00, 1, 'System generated member profile.', 2, '09526367041', '81763784', 'Separated', '26 Main St, Barangay 16, Metro Manila', 'Active'),
(27, 27, 'Regular', 'patricia27', '$2y$10$q/Uzg7JkWEJI6.knQgsnHeCRfmjSlZbhPSx8toA9G55ZcPmOym96y', 'Patricia', 'Dominic', 'Williams', 'patricia.williams27@example.com', 'default.png', '', '', '1974-07-14', NULL, '2026-03-05 09:45:42', 49439.00, 1, 'System generated member profile.', 2, '09659948660', '81486740', 'Separated', '27 Main St, Barangay 11, Metro Manila', 'Delisted'),
(28, 28, 'Regular', 'linda28', '$2y$10$ecdyHxwFyLVmtbH26HRF/u22zHtVio6REyxog7RvDJrzad68h3Bti', 'Linda', 'Alexander', 'Johnson', 'linda.johnson28@example.com', 'default.png', '', '', '1985-03-09', NULL, '2026-03-05 09:45:42', 43326.00, 0, 'System generated member profile.', 2, '09701484109', '84725083', 'Married', '28 Main St, Barangay 6, Metro Manila', 'Deceased'),
(29, 29, 'Regular', 'patricia29', '$2y$10$BVKGQPcOfz2t.CFo2KEUWOfLsPVql8ADgqGBwH0fWFiuvGR8LoSfy', 'Patricia', 'Dominic', 'Jones', 'patricia.jones29@example.com', 'default.png', '', '', '1973-05-19', NULL, '2026-03-05 09:45:42', 3579.00, 0, 'System generated member profile.', 2, '09190772676', '85634914', 'Widowed', '29 Main St, Barangay 10, Metro Manila', 'Under Litigation'),
(30, 30, 'Regular', 'patricia30', '$2y$10$eX.09mQyLYBhtXQLWad.Q.PJxdeyLBZHMnttW.AGCpB96W0HqjB6e', 'Patricia', 'Evangeline', 'Smith', 'patricia.smith30@example.com', 'default.png', 'Rev.', 'Jr.', '1971-07-11', NULL, '2026-03-05 09:45:42', 39932.00, 0, 'System generated member profile.', 2, '09582609522', '82465450', 'Widowed', '30 Main St, Barangay 12, Metro Manila', 'Active'),
(31, 31, 'Regular', 'mary31', '$2y$10$kWyzmcObNKswMQo94V1uHOiJ4noCFOG6ShG8hHjCnUvEvtPPdfnsa', 'Mary', 'Gabriel', 'Davis', 'mary.davis31@example.com', 'default.png', '', '', '1982-07-03', NULL, '2026-03-05 09:45:43', 42571.00, 1, 'System generated member profile.', 2, '09908461554', '82992449', 'Single', '31 Main St, Barangay 17, Metro Manila', 'Overdue'),
(32, 32, 'Regular', 'robert32', '$2y$10$LKUdsincASZ97T6sdiq4PeoXsHGYgmSfAHUiaXMl9p9l9byxrK17K', 'Robert', 'Evangeline', 'Williams', 'robert.williams32@example.com', 'default.png', '', '', '1990-04-14', NULL, '2026-03-05 09:45:43', 27755.00, 1, 'System generated member profile.', 2, '09448048535', '88426994', 'Widowed', '32 Main St, Barangay 20, Metro Manila', 'Delisted'),
(33, 33, 'Regular', 'linda33', '$2y$10$W55HcV69kZl.o7lYeCdE9u6eyD3C9bC6Rc09RKzVBKe3f2wYDD2GC', 'Linda', 'Alexander', 'Miller', 'linda.miller33@example.com', 'default.png', '', '', '1981-05-26', NULL, '2026-03-05 09:45:43', 3166.00, 1, 'System generated member profile.', 2, '09535549112', '85872967', 'Widowed', '33 Main St, Barangay 7, Metro Manila', 'Under Litigation'),
(34, 34, 'Regular', 'john34', '$2y$10$SLEUlEH4Z211AcMg0phox.FO25elGQDukB9.Ko/VOpA9m50YHAnLi', 'John', 'Dominic', 'Davis', 'john.davis34@example.com', 'default.png', '', '', '1997-06-15', NULL, '2026-03-05 09:45:43', 18404.00, 0, 'System generated member profile.', 2, '09678233521', '89243639', 'Widowed', '34 Main St, Barangay 17, Metro Manila', 'Deceased'),
(35, 35, 'Regular', 'john35', '$2y$10$bN2uZ5G3t6OX26xCZUD8ausYlXvXDumOFPDJGQ6Xz7rUwoNeijD4.', 'John', 'Evangeline', 'Johnson', 'john.johnson35@example.com', 'default.png', '', '', '2002-05-19', NULL, '2026-03-05 09:45:43', 40657.00, 1, 'System generated member profile.', 2, '09382833621', '83830056', 'Separated', '35 Main St, Barangay 9, Metro Manila', 'Deceased'),
(36, 36, 'Regular', 'patricia36', '$2y$10$d7spSuYc5WU976w4YytRf.5LejtPc/o.CIR4pgMl7.SAl7SxFoLXO', 'Patricia', 'Evangeline', 'Garcia', 'patricia.garcia36@example.com', 'default.png', '', '', '2001-03-22', NULL, '2026-03-05 09:45:43', 31884.00, 0, 'System generated member profile.', 2, '09290754834', '84167774', 'Widowed', '36 Main St, Barangay 20, Metro Manila', 'Under Litigation'),
(37, 37, 'Regular', 'patricia37', '$2y$10$ns4DPShhUT1CN.6MN8EAVu65aMFKCbmbjYlio9U/Ygg/Wt25LGvxO', 'Patricia', 'Alexander', 'Garcia', 'patricia.garcia37@example.com', 'default.png', '', '', '1994-01-08', NULL, '2026-03-05 09:45:43', 10867.00, 0, 'System generated member profile.', 2, '09881689947', '85451943', 'Married', '37 Main St, Barangay 9, Metro Manila', 'Delisted'),
(38, 38, 'Regular', 'michael38', '$2y$10$OyQjN21Jqoalav.WH.8y.ObiicJ.oeiqE8wvg2CGyoQcRkUGYuUN6', 'Michael', 'Alexander', 'Garcia', 'michael.garcia38@example.com', 'default.png', '', '', '1997-04-20', NULL, '2026-03-05 09:45:43', 20729.00, 0, 'System generated member profile.', 2, '09120206643', '84081675', 'Married', '38 Main St, Barangay 3, Metro Manila', 'Overdue'),
(39, 39, 'Regular', 'jennifer39', '$2y$10$YXUuqRFCFL7KDxhdHkaQn.VihR1vpZVsYPNWcib.bGJoFpWAKnyhy', 'Jennifer', 'Bernardo', 'Johnson', 'jennifer.johnson39@example.com', 'default.png', '', '', '1989-08-15', NULL, '2026-03-05 09:45:43', 26118.00, 0, 'System generated member profile.', 2, '09764525983', '87125619', 'Widowed', '39 Main St, Barangay 13, Metro Manila', 'Deceased'),
(40, 40, 'Regular', 'robert40', '$2y$10$9lEmvRdNdfm2uV5zOs0T5esTI3yAJuDKYmhb729dXVvOXU4sqpkwq', 'Robert', 'Bernardo', 'Davis', 'robert.davis40@example.com', 'default.png', 'Rev.', '', '1971-04-01', NULL, '2026-03-05 09:45:43', 28477.00, 0, 'System generated member profile.', 2, '09793059662', '88487633', 'Married', '40 Main St, Barangay 5, Metro Manila', 'Under Litigation'),
(41, 41, 'Regular', 'jennifer41', '$2y$10$AVwVFvpT01tl7uiDWhM/g.xdBkbIOYu8XWPuDbc0UsKljv9.jIZiq', 'Jennifer', 'Evangeline', 'Smith', 'jennifer.smith41@example.com', 'default.png', '', '', '1998-04-26', NULL, '2026-03-05 09:45:43', 38922.00, 0, 'System generated member profile.', 2, '09765732740', '83002584', 'Widowed', '41 Main St, Barangay 14, Metro Manila', 'Overdue'),
(42, 42, 'Regular', 'james42', '$2y$10$24bUmZ1T1FBCXcWjMlpIz.xE9tL.eflPLEwPlpxOgrXWD6PbayEHK', 'James', 'Bernardo', 'Johnson', 'james.johnson42@example.com', 'default.png', '', '', '1982-05-03', NULL, '2026-03-05 09:45:43', 19273.00, 1, 'System generated member profile.', 2, '09741730751', '83883451', 'Separated', '42 Main St, Barangay 3, Metro Manila', 'Under Litigation'),
(43, 43, 'Regular', 'linda43', '$2y$10$Ri79fBWb.vwA.QfVzcan7..YBwwfSFGVEfovqazQEqcIRfn/O66D.', 'Linda', 'Fernando', 'Miller', 'linda.miller43@example.com', 'default.png', '', '', '1970-02-26', NULL, '2026-03-05 09:45:43', 39806.00, 0, 'System generated member profile.', 2, '09465448674', '89733035', 'Single', '43 Main St, Barangay 17, Metro Manila', 'On-Hold'),
(44, 44, 'Regular', 'mary44', '$2y$10$.wwF3S1SWhKvYZWmP04aOe63TG/Pb9G1poEwjx7jNYpanFGqwXseW', 'Mary', 'Catherine', 'Miller', 'mary.miller44@example.com', 'default.png', '', '', '2005-07-08', NULL, '2026-03-05 09:45:43', 19200.00, 0, 'System generated member profile.', 2, '09170231959', '86845775', 'Married', '44 Main St, Barangay 3, Metro Manila', 'Delisted'),
(45, 45, 'Regular', 'linda45', '$2y$10$BRCFXm6Hftu4wzxoU76R5OclbuDk8Qhcj8Z3uxqeHV2fxEzwfnLKK', 'Linda', 'Gabriel', 'Garcia', 'linda.garcia45@example.com', 'default.png', '', 'Jr.', '2005-12-09', NULL, '2026-03-05 09:45:44', 22253.00, 1, 'System generated member profile.', 2, '09375598727', '89599446', 'Single', '45 Main St, Barangay 3, Metro Manila', 'Active'),
(46, 46, 'Regular', 'patricia46', '$2y$10$J3kXcXQwRlMiojUYg507jeGX1j5N92jjVSQdHEwAhlqHMivA0Ymeu', 'Patricia', 'Dominic', 'Smith', 'patricia.smith46@example.com', 'default.png', '', '', '2003-07-13', NULL, '2026-03-05 09:45:44', 44282.00, 0, 'System generated member profile.', 2, '09136667771', '82278044', 'Separated', '46 Main St, Barangay 2, Metro Manila', 'Active'),
(47, 47, 'Regular', 'mary47', '$2y$10$JwizB1URJPIokoo06PaztuLScWNBcc.y0DK/jCIYWV88PDY.X7uay', 'Mary', 'Gabriel', 'Miller', 'mary.miller47@example.com', 'default.png', '', '', '2002-10-15', NULL, '2026-03-05 09:45:44', 29341.00, 1, 'System generated member profile.', 2, '09475513973', '89271130', 'Married', '47 Main St, Barangay 4, Metro Manila', 'Delisted'),
(48, 48, 'Regular', 'mary48', '$2y$10$eA70poNfHc8obXgeAeGCnekjgupwI/5p/uWDssXcIwDGZ9q327uVO', 'Mary', 'Dominic', 'Davis', 'mary.davis48@example.com', 'default.png', '', '', '1982-07-01', NULL, '2026-03-05 09:45:44', 19340.00, 1, 'System generated member profile.', 2, '09556589754', '89782895', 'Married', '48 Main St, Barangay 17, Metro Manila', 'On-Hold'),
(49, 49, 'Regular', 'james49', '$2y$10$dxeOlibwRmgJeRpNY7CIYeYXcoJdP.my8bCfh4c2ChdUFU6vztR5u', 'James', 'Bernardo', 'Williams', 'james.williams49@example.com', 'default.png', '', '', '1975-01-12', NULL, '2026-03-05 09:45:44', 42826.00, 1, 'System generated member profile.', 2, '09631711250', '81530783', 'Married', '49 Main St, Barangay 13, Metro Manila', 'On-Hold'),
(50, 50, 'Regular', 'jennifer50', '$2y$10$9LKSfi3tWN22kivUpdBOjugVZvX/l92F7Qlp7pxkPnLoyFEsA.F5u', 'Jennifer', 'Fernando', 'Johnson', 'jennifer.johnson50@example.com', 'default.png', 'Rev.', '', '2005-09-26', NULL, '2026-03-05 09:45:44', 35950.00, 0, 'System generated member profile.', 2, '09469226303', '86652523', 'Married', '50 Main St, Barangay 17, Metro Manila', 'Active'),
(51, 51, 'Associate', 'mary51', '$2y$10$tTr75cFq2srm8TpON8FVWO8cckMQRAcdY6EiQiYEvo8Nz47A0J5sW', 'Mary', 'Fernando', 'Miller', 'mary.miller51@example.com', 'default.png', '', '', '1975-05-02', NULL, '2026-03-05 09:45:44', 13958.00, 0, 'System generated member profile.', 2, '09692390210', '89793228', 'Single', '51 Main St, Barangay 7, Metro Manila', 'Overdue'),
(52, 52, 'Associate', 'mary52', '$2y$10$iQHHkO2QUjBR9RuUFJ2XfOrVkzE5A4nAueL1cm0DsCi77K8PK65Oi', 'Mary', 'Dominic', 'Johnson', 'mary.johnson52@example.com', 'default.png', '', '', '1985-01-24', NULL, '2026-03-05 09:45:44', 6564.00, 1, 'System generated member profile.', 2, '09305836614', '89425804', 'Married', '52 Main St, Barangay 18, Metro Manila', 'Under Litigation'),
(53, 53, 'Associate', 'mary53', '$2y$10$yX6yipVgakIILoRtGaP9Xeo.Wg86wzIJiRHsJNoZURQoNHXhtnZ9S', 'Mary', 'Evangeline', 'Smith', 'mary.smith53@example.com', 'default.png', '', '', '1993-08-22', NULL, '2026-03-05 09:45:44', 19744.00, 1, 'System generated member profile.', 2, '09181111286', '86383925', 'Separated', '53 Main St, Barangay 19, Metro Manila', 'Under Litigation'),
(54, 54, 'Associate', 'james54', '$2y$10$QtUM26bUY1bTw8MrT8MT/eLcfBAgyoX5Iv5s.lK73b8P2OYA0xz1i', 'James', 'Evangeline', 'Miller', 'james.miller54@example.com', 'default.png', '', '', '1984-06-18', NULL, '2026-03-05 09:45:44', 2205.00, 1, 'System generated member profile.', 2, '09319833656', '83454456', 'Married', '54 Main St, Barangay 3, Metro Manila', 'Under Litigation'),
(55, 55, 'Associate', 'james55', '$2y$10$e.VYp4LMhPRx2tD1liCMVOWUj9WVx3Off3VQcvoCS/DMe7xIZ7CPC', 'James', 'Catherine', 'Williams', 'james.williams55@example.com', 'default.png', '', '', '1998-04-15', NULL, '2026-03-05 09:45:44', 28405.00, 1, 'System generated member profile.', 2, '09258378732', '81210398', 'Married', '55 Main St, Barangay 3, Metro Manila', 'Overdue'),
(56, 56, 'Associate', 'john56', '$2y$10$GtmuLGgL0SHONNtjKhmgI.sV8VhxDpZ.qhIGmZEzFyA5xfzvLO9lK', 'John', 'Fernando', 'Davis', 'john.davis56@example.com', 'default.png', '', '', '2005-02-14', NULL, '2026-03-05 09:45:44', 44153.00, 0, 'System generated member profile.', 2, '09346975999', '87660941', 'Widowed', '56 Main St, Barangay 2, Metro Manila', 'Deceased'),
(57, 57, 'Associate', 'linda57', '$2y$10$K0RnATG.1mpd8bwUQN6RVe18U5OqgvG64x9a168kvjQT/1jmGwA4C', 'Linda', 'Gabriel', 'Johnson', 'linda.johnson57@example.com', 'default.png', '', '', '1987-11-22', NULL, '2026-03-05 09:45:44', 46270.00, 1, 'System generated member profile.', 2, '09377447720', '88642101', 'Separated', '57 Main St, Barangay 18, Metro Manila', 'Active'),
(58, 58, 'Associate', 'patricia58', '$2y$10$oJX6yY4uwoI.JKUm6UUvg.pgk2g.M3xF8wfV5319xtyJnIHg8OmRO', 'Patricia', 'Evangeline', 'Garcia', 'patricia.garcia58@example.com', 'default.png', '', '', '1972-08-24', NULL, '2026-03-05 09:45:44', 29107.00, 1, 'System generated member profile.', 2, '09105260193', '85257735', 'Married', '58 Main St, Barangay 5, Metro Manila', 'Under Litigation'),
(59, 59, 'Associate', 'linda59', '$2y$10$jGyxApGTTLdk5CcbefbTVOPP07PbS88pF0PGTLe1y7pjAu6ip8MIW', 'Linda', 'Fernando', 'Brown', 'linda.brown59@example.com', 'default.png', '', '', '1991-09-25', NULL, '2026-03-05 09:45:45', 22119.00, 1, 'System generated member profile.', 2, '09661688947', '88655177', 'Separated', '59 Main St, Barangay 12, Metro Manila', 'Overdue'),
(60, 60, 'Associate', 'james60', '$2y$10$WyGwxi42XQT0IQf474lEYO43BM7Mj/qYzDwFL2Sehxlnkd6yiqWFS', 'James', 'Fernando', 'Williams', 'james.williams60@example.com', 'default.png', 'Rev.', 'Jr.', '1981-08-10', NULL, '2026-03-05 09:45:45', 14270.00, 0, 'System generated member profile.', 2, '09276646915', '82966459', 'Married', '60 Main St, Barangay 20, Metro Manila', 'Active'),
(61, 61, 'Associate', 'john61', '$2y$10$Ce74k.QQVZkHUjzHHVVAteigWGYK6a2RkOKmQrCaLTfQJ2mH9Qr92', 'John', 'Fernando', 'Davis', 'john.davis61@example.com', 'default.png', '', '', '1993-04-04', NULL, '2026-03-05 09:45:45', 41119.00, 0, 'System generated member profile.', 2, '09638268411', '87941983', 'Married', '61 Main St, Barangay 13, Metro Manila', 'Active'),
(62, 62, 'Associate', 'jennifer62', '$2y$10$MpGWPp4QAEk2q4Rwj5Sep.jxiOqvHueKrXGqEsycbG4QZEKm99nl2', 'Jennifer', 'Evangeline', 'Johnson', 'jennifer.johnson62@example.com', 'default.png', '', '', '1985-08-05', NULL, '2026-03-05 09:45:45', 1823.00, 1, 'System generated member profile.', 2, '09423120176', '89304416', 'Single', '62 Main St, Barangay 8, Metro Manila', 'Deceased'),
(63, 63, 'Associate', 'linda63', '$2y$10$tD1wusjBpwE7maOSbQDhQ.C5A4CC/w.LguUUrcjQHkHHukj/U.i.m', 'Linda', 'Alexander', 'Johnson', 'linda.johnson63@example.com', 'default.png', '', '', '1996-06-28', NULL, '2026-03-05 09:45:45', 44252.00, 0, 'System generated member profile.', 2, '09117755456', '88829599', 'Single', '63 Main St, Barangay 7, Metro Manila', 'Under Litigation'),
(64, 64, 'Associate', 'mary64', '$2y$10$9Z61RMpRQuopcRMhwLusJuNVuaGc5SOhtb.5zhCAt9Ppoh16R5GuK', 'Mary', 'Dominic', 'Williams', 'mary.williams64@example.com', 'default.png', '', '', '2000-09-17', NULL, '2026-03-05 09:45:45', 11434.00, 1, 'System generated member profile.', 2, '09653402220', '83346402', 'Separated', '64 Main St, Barangay 14, Metro Manila', 'Overdue'),
(65, 65, 'Associate', 'linda65', '$2y$10$P5x.k.JHkBsCdt/g/PJoruXFjittDxcfDwVnkVfZeCC2wPtf3BELq', 'Linda', 'Gabriel', 'Davis', 'linda.davis65@example.com', 'default.png', '', '', '1997-07-16', NULL, '2026-03-05 09:45:45', 37511.00, 0, 'System generated member profile.', 2, '09554868968', '84625602', 'Single', '65 Main St, Barangay 5, Metro Manila', 'Active'),
(66, 66, 'Associate', 'linda66', '$2y$10$Jw21cHDFLLXcEZmzoDNCm.CYqn2ho6khduQVsvTEy0WPLWyxOGzJG', 'Linda', 'Fernando', 'Jones', 'linda.jones66@example.com', 'default.png', '', '', '1979-08-17', NULL, '2026-03-05 09:45:45', 11789.00, 0, 'System generated member profile.', 2, '09948795805', '82811566', 'Married', '66 Main St, Barangay 4, Metro Manila', 'Delisted'),
(67, 67, 'Associate', 'michael67', '$2y$10$BxaUIQeTwg/h2g1l8tJae.q17kHJyfhw9TjATD0DmLzdkme9sycfm', 'Michael', 'Gabriel', 'Johnson', 'michael.johnson67@example.com', 'default.png', '', '', '1991-01-24', NULL, '2026-03-05 09:45:45', 7097.00, 0, 'System generated member profile.', 2, '09708966881', '86625706', 'Widowed', '67 Main St, Barangay 10, Metro Manila', 'Delisted'),
(68, 68, 'Associate', 'patricia68', '$2y$10$cF7wbW0iVcHuHaUFnTXX7eKV6/Gkd9Nore0l2zQUV5l/4YwxgMIyS', 'Patricia', 'Evangeline', 'Davis', 'patricia.davis68@example.com', 'default.png', '', '', '1996-03-21', NULL, '2026-03-05 09:45:45', 11382.00, 1, 'System generated member profile.', 2, '09651570222', '85175678', 'Widowed', '68 Main St, Barangay 15, Metro Manila', 'Deceased'),
(69, 69, 'Associate', 'michael69', '$2y$10$vqSR/HwHrHH.MNz80uF/5eHAUDY8aVO7t5jJEQCIxDrxcAC5CjltS', 'Michael', 'Bernardo', 'Miller', 'michael.miller69@example.com', 'default.png', '', '', '1977-09-12', NULL, '2026-03-05 09:45:45', 6580.00, 1, 'System generated member profile.', 2, '09423788273', '82034739', 'Single', '69 Main St, Barangay 4, Metro Manila', 'Active'),
(70, 70, 'Associate', 'robert70', '$2y$10$RLeemlRQJDDJo1nL82kpse5xWsG2Zo1.JVDz1ULyeWCcOYvhL5Dcm', 'Robert', 'Bernardo', 'Davis', 'robert.davis70@example.com', 'default.png', 'Rev.', '', '1977-12-14', NULL, '2026-03-05 09:45:45', 31979.00, 1, 'System generated member profile.', 2, '09620567211', '83068726', 'Married', '70 Main St, Barangay 12, Metro Manila', 'Delisted'),
(71, 71, 'Associate', 'michael71', '$2y$10$RJ6tGNKEbUjiG20C0JvWGOwl5.eC/6.AlHOlELv4beH1T/D8T/Bp6', 'Michael', 'Evangeline', 'Johnson', 'michael.johnson71@example.com', 'default.png', '', '', '1979-07-04', NULL, '2026-03-05 09:45:45', 14722.00, 1, 'System generated member profile.', 2, '09742249787', '85437173', 'Single', '71 Main St, Barangay 14, Metro Manila', 'Under Litigation'),
(72, 72, 'Associate', 'mary72', '$2y$10$Rzm50OM9ZE97qByKkc8QL.eaS1jD06Gz4bi8o6Yz4xiG8rkLHoklu', 'Mary', 'Catherine', 'Johnson', 'mary.johnson72@example.com', 'default.png', '', '', '1986-12-21', NULL, '2026-03-05 09:45:46', 17329.00, 0, 'System generated member profile.', 2, '09705387671', '84873662', 'Widowed', '72 Main St, Barangay 6, Metro Manila', 'Active'),
(73, 73, 'Associate', 'michael73', '$2y$10$trzJifr2X7UB1ODR7/H30uqIwSyz4iKZL/Rfg90lUMN68L5rOYtBu', 'Michael', 'Evangeline', 'Davis', 'michael.davis73@example.com', 'default.png', '', '', '1980-07-14', NULL, '2026-03-05 09:45:46', 25612.00, 0, 'System generated member profile.', 2, '09692220229', '85590988', 'Separated', '73 Main St, Barangay 12, Metro Manila', 'Overdue'),
(74, 74, 'Associate', 'james74', '$2y$10$BOWfTjG8AaRENw1yRkxPsO6KXPQr4FOMgYJAFYz4.oKNNgUXs3rvW', 'James', 'Evangeline', 'Johnson', 'james.johnson74@example.com', 'default.png', '', '', '1980-01-03', NULL, '2026-03-05 09:45:46', 23531.00, 1, 'System generated member profile.', 2, '09199050547', '84806526', 'Married', '74 Main St, Barangay 16, Metro Manila', 'On-Hold'),
(75, 75, 'Associate', 'john75', '$2y$10$YPGDrlDVHdNBSF4Eaq1Yy.g0LIWeXF1U0ZCCFc3J6YwWN5z50m4mm', 'John', 'Catherine', 'Davis', 'john.davis75@example.com', 'default.png', '', 'Jr.', '1997-02-14', NULL, '2026-03-05 09:45:46', 31268.00, 0, 'System generated member profile.', 2, '09368096450', '85261782', 'Single', '75 Main St, Barangay 16, Metro Manila', 'Overdue'),
(76, 76, 'Associate', 'james76', '$2y$10$Q72ZZm6RxeckbBycQoXrreq0NP5wyFqU.uK0SJb2tuMn7pVCimPmu', 'James', 'Dominic', 'Garcia', 'james.garcia76@example.com', 'default.png', '', '', '1999-01-10', NULL, '2026-03-05 09:45:46', 35438.00, 0, 'System generated member profile.', 2, '09797306394', '89426044', 'Single', '76 Main St, Barangay 14, Metro Manila', 'Deceased'),
(77, 77, 'Associate', 'mary77', '$2y$10$5fOiQ8gMtpVCB9gpKlEdu.WyUqKeqoxxxspMLYjSV1jfuZTyJ/Pa2', 'Mary', 'Catherine', 'Miller', 'mary.miller77@example.com', 'default.png', '', '', '1994-06-15', NULL, '2026-03-05 09:45:46', 40744.00, 1, 'System generated member profile.', 2, '09874241230', '83111337', 'Married', '77 Main St, Barangay 17, Metro Manila', 'Overdue'),
(78, 78, 'Associate', 'linda78', '$2y$10$8Xws7m8EXM/UqAUHdfRU..VR9RFoA72DGjLCsXElhbdROhuhIszYO', 'Linda', 'Gabriel', 'Garcia', 'linda.garcia78@example.com', 'default.png', '', '', '1978-06-08', NULL, '2026-03-05 09:45:46', 15630.00, 0, 'System generated member profile.', 2, '09392852651', '88655264', 'Single', '78 Main St, Barangay 14, Metro Manila', 'Overdue'),
(79, 79, 'Associate', 'john79', '$2y$10$83ARu6NDsKTCW3dRlC6xXuDfJfuyaApjphep1xDPSJGtZwuq/WIgG', 'John', 'Alexander', 'Garcia', 'john.garcia79@example.com', 'default.png', '', '', '1988-02-13', NULL, '2026-03-05 09:45:46', 3590.00, 1, 'System generated member profile.', 2, '09530216088', '89751536', 'Married', '79 Main St, Barangay 4, Metro Manila', 'Overdue'),
(80, 80, 'Associate', 'john80', '$2y$10$TQPRIRBUHEtXdvHJgnj7iu71tMtL9juXL1hAuYNpODiSwMcC.tpm2', 'John', 'Fernando', 'Miller', 'john.miller80@example.com', 'default.png', 'Rev.', '', '2000-10-23', NULL, '2026-03-05 09:45:46', 6466.00, 1, 'System generated member profile.', 2, '09788830031', '89465595', 'Single', '80 Main St, Barangay 17, Metro Manila', 'Delisted'),
(81, 81, 'Associate', 'james81', '$2y$10$MdM/IRzMrPVCgzVb.1VkQ.DnVCIw.49nAGQqCUim1.7fBFYDVYooy', 'James', 'Catherine', 'Smith', 'james.smith81@example.com', 'default.png', '', '', '1994-01-28', NULL, '2026-03-05 09:45:46', 34698.00, 1, 'System generated member profile.', 2, '09747221534', '85110039', 'Separated', '81 Main St, Barangay 15, Metro Manila', 'Overdue'),
(82, 82, 'Associate', 'james82', '$2y$10$hzX2rnvmGF5g2RKdM1I4PeytiL6TLUAhWe9cLypCSSatyvUnQvsLu', 'James', 'Evangeline', 'Jones', 'james.jones82@example.com', 'default.png', '', '', '1982-12-14', NULL, '2026-03-05 09:45:46', 19261.00, 1, 'System generated member profile.', 2, '09563745525', '86676930', 'Single', '82 Main St, Barangay 6, Metro Manila', 'Overdue'),
(83, 83, 'Associate', 'mary83', '$2y$10$MJU/Y30tJkGSP5noel.rresG4ekmVC4hmPy.BLLYfj4EF4GJd7MSu', 'Mary', 'Bernardo', 'Jones', 'mary.jones83@example.com', 'default.png', '', '', '1999-11-16', NULL, '2026-03-05 09:45:46', 49113.00, 0, 'System generated member profile.', 2, '09924754050', '81797239', 'Separated', '83 Main St, Barangay 10, Metro Manila', 'Deceased'),
(84, 84, 'Associate', 'mary84', '$2y$10$01psvGGTXwjq7wuBpXQdQOCvMMmL3qXNDMhrWv/161o1H/BjOjCnK', 'Mary', 'Fernando', 'Jones', 'mary.jones84@example.com', 'default.png', '', '', '1987-05-09', NULL, '2026-03-05 09:45:46', 27745.00, 0, 'System generated member profile.', 2, '09978151500', '82721193', 'Separated', '84 Main St, Barangay 13, Metro Manila', 'Overdue'),
(85, 85, 'Associate', 'mary85', '$2y$10$n6UuiRWZ9u9chX2qMUP8Wuc/2PPSFwe334wtyrwEjbbJi8fRQR6uC', 'Mary', 'Dominic', 'Smith', 'mary.smith85@example.com', 'default.png', '', '', '1970-09-07', NULL, '2026-03-05 09:45:47', 36425.00, 1, 'System generated member profile.', 2, '09244407359', '82608829', 'Single', '85 Main St, Barangay 13, Metro Manila', 'Deceased'),
(86, 86, 'Associate', 'robert86', '$2y$10$pSr7eTrOEIFYmvi.xUPfCu/MDDMVBfMPxEqn/lqxBahiX3sUn7/Hu', 'Robert', 'Alexander', 'Garcia', 'robert.garcia86@example.com', 'default.png', '', '', '1986-12-26', NULL, '2026-03-05 09:45:47', 45249.00, 0, 'System generated member profile.', 2, '09686766702', '86509978', 'Separated', '86 Main St, Barangay 15, Metro Manila', 'Deceased'),
(87, 87, 'Associate', 'patricia87', '$2y$10$00jLvKkQ1ZS3y77kvBtur.HIwYPdPASMV.7GQXfXN60z8v71zlgx.', 'Patricia', 'Fernando', 'Smith', 'patricia.smith87@example.com', 'default.png', '', '', '1988-05-14', NULL, '2026-03-05 09:45:47', 43032.00, 1, 'System generated member profile.', 2, '09219856273', '82370601', 'Widowed', '87 Main St, Barangay 7, Metro Manila', 'Deceased'),
(88, 88, 'Associate', 'robert88', '$2y$10$kafVAj3aOXKJ8E/GjPOMWe8MtPOB0nJsv1L/YPuoc9xTn/MIm7wEq', 'Robert', 'Fernando', 'Smith', 'robert.smith88@example.com', 'default.png', '', '', '1988-07-12', NULL, '2026-03-05 09:45:47', 35459.00, 0, 'System generated member profile.', 2, '09625035724', '89115844', 'Separated', '88 Main St, Barangay 7, Metro Manila', 'Overdue'),
(89, 89, 'Associate', 'james89', '$2y$10$STyzO2.tF7UnNQFDhe9GBupto0fLPW9FvNic8MNsmt0RIfQJMC/wa', 'James', 'Bernardo', 'Davis', 'james.davis89@example.com', 'default.png', '', '', '1990-02-11', NULL, '2026-03-05 09:45:47', 17850.00, 1, 'System generated member profile.', 2, '09960021441', '88185258', 'Single', '89 Main St, Barangay 20, Metro Manila', 'On-Hold'),
(90, 90, 'Associate', 'mary90', '$2y$10$HXzsN1qFgWlyRPJwklYane/zl5DgVQ9vLygt4KKlAY4ByoDQ9NU/i', 'Mary', 'Bernardo', 'Jones', 'mary.jones90@example.com', 'default.png', 'Rev.', 'Jr.', '1996-04-08', NULL, '2026-03-05 09:45:47', 16115.00, 0, 'System generated member profile.', 2, '09861653048', '83617986', 'Separated', '90 Main St, Barangay 20, Metro Manila', 'Overdue'),
(91, 91, 'Associate', 'robert91', '$2y$10$a/Qu4eIHU5Nc59XipPo0j.yfWW1wrCXjt3SnZqelRbMW/KERP9FLS', 'Robert', 'Dominic', 'Johnson', 'robert.johnson91@example.com', 'default.png', '', '', '1999-08-01', NULL, '2026-03-05 09:45:47', 49263.00, 0, 'System generated member profile.', 2, '09407190087', '89263460', 'Separated', '91 Main St, Barangay 17, Metro Manila', 'Under Litigation'),
(92, 92, 'Associate', 'mary92', '$2y$10$A.Jxsx3U310wfcFOqQDpt.uZZCN5Uff9/zsGz0UTR31u3bP11wUDi', 'Mary', 'Bernardo', 'Johnson', 'mary.johnson92@example.com', 'default.png', '', '', '1988-06-19', NULL, '2026-03-05 09:45:47', 15629.00, 1, 'System generated member profile.', 2, '09474994784', '81481889', 'Married', '92 Main St, Barangay 17, Metro Manila', 'On-Hold'),
(93, 93, 'Associate', 'linda93', '$2y$10$q4dA4/4Z9jFDoL4E4mQvROwZ5sLBCS89S9QXbLQtQc7nND0NpQIPi', 'Linda', 'Dominic', 'Johnson', 'linda.johnson93@example.com', 'default.png', '', '', '1971-06-15', NULL, '2026-03-05 09:45:47', 36770.00, 1, 'System generated member profile.', 2, '09966417622', '86840203', 'Married', '93 Main St, Barangay 15, Metro Manila', 'Deceased'),
(94, 94, 'Associate', 'linda94', '$2y$10$2hW4Mg.YDxM.9NF4k/K2UOXOYTuSXwXx0zW4dL00pujhfRwLInhyy', 'Linda', 'Catherine', 'Davis', 'linda.davis94@example.com', 'default.png', '', '', '1971-11-28', NULL, '2026-03-05 09:45:47', 1832.00, 0, 'System generated member profile.', 2, '09707387295', '86198974', 'Single', '94 Main St, Barangay 1, Metro Manila', 'Delisted'),
(95, 95, 'Associate', 'mary95', '$2y$10$3HCrhQrtcO5pDw02DnZsjunmrl4Adgpiq5Q.zgyhKsRrRcOWbKbYG', 'Mary', 'Dominic', 'Johnson', 'mary.johnson95@example.com', 'default.png', '', '', '1979-06-18', NULL, '2026-03-05 09:45:47', 46485.00, 0, 'System generated member profile.', 2, '09828742864', '89831260', 'Married', '95 Main St, Barangay 15, Metro Manila', 'Delisted'),
(96, 96, 'Associate', 'mary96', '$2y$10$B0KwNOnl/Ney3dNn2itFr.wrrt7E/XbTvwwbLf29eNnxgxmuL.YbW', 'Mary', 'Dominic', 'Miller', 'mary.miller96@example.com', 'default.png', '', '', '1985-04-19', NULL, '2026-03-05 09:45:47', 43775.00, 0, 'System generated member profile.', 2, '09423485568', '89864207', 'Single', '96 Main St, Barangay 1, Metro Manila', 'Under Litigation'),
(97, 97, 'Associate', 'patricia97', '$2y$10$qMqpTF/CTiCTwkUAyB14kuJF/KvWmB/tQcqnrinUfTgMzh6AqZPba', 'Patricia', 'Bernardo', 'Davis', 'patricia.davis97@example.com', 'default.png', '', '', '1971-06-14', NULL, '2026-03-05 09:45:47', 10281.00, 1, 'System generated member profile.', 2, '09368629354', '84018774', 'Married', '97 Main St, Barangay 13, Metro Manila', 'Under Litigation'),
(98, 98, 'Associate', 'mary98', '$2y$10$H30NotnPUFDowpYah9stT.G4aH4cs9.paHLW7THXoqIciKPIMfGYa', 'Mary', 'Fernando', 'Williams', 'mary.williams98@example.com', 'default.png', '', '', '1980-05-05', NULL, '2026-03-05 09:45:47', 38077.00, 0, 'System generated member profile.', 2, '09814157622', '88449434', 'Widowed', '98 Main St, Barangay 13, Metro Manila', 'Under Litigation'),
(99, 99, 'Associate', 'mary99', '$2y$10$3VPmLx97eqaCCK40rPPli.C10UZF4IjL6Dbtojksr2dVK2rKaSHkW', 'Mary', 'Evangeline', 'Jones', 'mary.jones99@example.com', 'default.png', '', '', '1978-11-26', NULL, '2026-03-05 09:45:48', 14192.00, 0, 'System generated member profile.', 2, '09183101297', '81364247', 'Married', '99 Main St, Barangay 10, Metro Manila', 'Under Litigation'),
(100, 100, 'Associate', 'robert100', '$2y$10$p4hJNM9AY8YLCS0bHm1mG.z/7JA2TC1k/QUe0iazTum5QR3xMD4z.', 'Robert', 'Dominic', 'Garcia', 'robert.garcia100@example.com', 'default.png', 'Rev.', '', '1988-11-27', NULL, '2026-03-05 09:45:48', 6906.00, 0, 'System generated member profile.', 2, '09416041103', '86756246', 'Married', '100 Main St, Barangay 19, Metro Manila', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`) VALUES
(1, 'admin'),
(2, 'member');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `loan`
--
ALTER TABLE `loan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `loan`
--
ALTER TABLE `loan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `loan`
--
ALTER TABLE `loan`
  ADD CONSTRAINT `loan_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`);

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
