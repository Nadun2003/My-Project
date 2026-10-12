-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 06, 2026 at 07:10 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `paradise_travel_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `booking_no` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `guide_id` int(11) DEFAULT NULL,
  `hotel_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `adults` int(11) DEFAULT 1,
  `children` int(11) DEFAULT 0,
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `payment_status` enum('pending','partial','paid') DEFAULT 'pending',
  `special_requests` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_no`, `customer_id`, `package_id`, `guide_id`, `hotel_id`, `start_date`, `end_date`, `adults`, `children`, `total_amount`, `paid_amount`, `status`, `payment_status`, `special_requests`, `created_at`) VALUES
(1, 'TRV20251101001', 2, 1, NULL, 4, '2025-12-15', '2025-12-20', 2, 1, 112500.00, 112500.00, 'confirmed', 'paid', NULL, '2026-07-15 21:19:57'),
(2, 'TRV20260716274', 5, 1, NULL, 4, '2026-07-18', '2026-07-24', 1, 2, 91500.00, 91500.00, 'confirmed', 'paid', '', '2026-07-15 21:33:11'),
(6, 'TRV20260716860', 10, 27, NULL, 4, '2026-07-16', '2026-07-19', 1, 1, 34500.00, 34500.00, 'confirmed', 'paid', '', '2026-07-16 16:24:03'),
(22, 'TRV20260930785', 9, 8, 13, 4, '2026-09-30', '2026-10-08', 16, 10, 1998500.00, 1998500.00, 'confirmed', 'paid', '', '2026-09-30 10:45:00'),
(24, 'TRV20261004819', 9, 34, 23, 21, '2026-10-04', '2026-10-06', 13, 3, 190000.00, 190000.00, 'confirmed', 'paid', '', '2026-10-04 17:47:51');

-- --------------------------------------------------------

--
-- Table structure for table `customer_profiles`
--

CREATE TABLE `customer_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nic` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_profiles`
--

INSERT INTO `customer_profiles` (`id`, `user_id`, `nic`, `address`, `date_of_birth`, `nationality`) VALUES
(1, 2, '982345678V', 'No.15, Galle Road, Colombo 03', NULL, 'Sri Lankan'),
(2, 5, '200332411796', 'nadunpoorna98@gmail.com', '2026-07-31', 'srilanka'),
(3, 9, '200332411796', '', '0000-00-00', ''),
(4, 10, '200332411796', '', '2026-07-22', 'srilanka'),
(6, 22, '200134657876', 'galle road , mathara', '2026-10-08', 'srilankan'),
(7, 25, '200319312198', 'Godauda ,Kottegoda, matara', '2003-11-07', 'Sri lanka'),
(8, 26, '200385800243', 'galle road ,mathara', '2026-10-07', 'srilankan');

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `recipient_email` varchar(100) DEFAULT NULL,
  `recipient_type` varchar(50) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'sent',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `rating_service` int(11) DEFAULT 5,
  `rating_guide` int(11) DEFAULT 5,
  `rating_hotel` int(11) DEFAULT 5,
  `rating_value` int(11) DEFAULT 5,
  `would_recommend` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `booking_id`, `customer_id`, `rating`, `comment`, `created_at`, `rating_service`, `rating_guide`, `rating_hotel`, `rating_value`, `would_recommend`) VALUES
(1, 1, 2, 5, 'Amazing tour! The guide was very knowledgeable and friendly. Highly recommended!', '2026-07-15 21:19:57', 5, 5, 5, 5, 1);

-- --------------------------------------------------------

--
-- Table structure for table `guide_profiles`
--

CREATE TABLE `guide_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `experience_years` int(11) DEFAULT 0,
  `languages` text DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `availability` enum('available','busy','off') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guide_profiles`
--

INSERT INTO `guide_profiles` (`id`, `user_id`, `experience_years`, `languages`, `specialization`, `hourly_rate`, `rating`, `total_reviews`, `availability`) VALUES
(3, 11, 1, 'English, Sinhala', '', 1000.00, 0.00, 0, 'available'),
(4, 13, 2, 'English, Sinhala', 'cultuer', 16000.00, 0.00, 0, 'available'),
(6, 23, 2, 'arbii,english,tamil', 'wild life', 1000.00, 0.00, 0, 'available');

-- --------------------------------------------------------

--
-- Table structure for table `hotel_bookings`
--

CREATE TABLE `hotel_bookings` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `hotel_id` int(11) DEFAULT NULL,
  `hotel_name` varchar(200) DEFAULT NULL,
  `room_type` varchar(100) DEFAULT NULL,
  `check_in_date` date DEFAULT NULL,
  `check_out_date` date DEFAULT NULL,
  `room_count` int(11) DEFAULT 1,
  `total_price` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hotel_bookings`
--

INSERT INTO `hotel_bookings` (`id`, `booking_id`, `hotel_id`, `hotel_name`, `room_type`, `check_in_date`, `check_out_date`, `room_count`, `total_price`, `status`, `created_at`) VALUES
(1, 2, 4, '', 'Standard', '2026-07-18', '2026-07-24', 1, 27450.00, 'pending', '2026-07-15 21:33:12'),
(5, 6, 4, '', 'Deluxe', '2026-07-16', '2026-07-19', 1, 10350.00, 'pending', '2026-07-16 16:24:03'),
(21, 22, 4, 'koggala ', 'Deluxe', '2026-09-30', '2026-10-08', 1, 599550.00, 'pending', '2026-09-30 10:45:00'),
(23, 24, 21, '', 'Deluxe', '2026-10-04', '2026-10-06', 3, 57000.00, 'pending', '2026-10-04 17:47:51');

-- --------------------------------------------------------

--
-- Table structure for table `hotel_profiles`
--

CREATE TABLE `hotel_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `hotel_name` varchar(200) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `stars` int(11) DEFAULT 3,
  `room_count` int(11) DEFAULT 0,
  `price_per_night` decimal(10,2) DEFAULT NULL,
  `amenities` text DEFAULT NULL,
  `check_in_time` varchar(20) DEFAULT NULL,
  `check_out_time` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hotel_profiles`
--

INSERT INTO `hotel_profiles` (`id`, `user_id`, `hotel_name`, `location`, `stars`, `room_count`, `price_per_night`, `amenities`, `check_in_time`, `check_out_time`) VALUES
(1, 4, 'Sigiriya Rock View Hotel', 'Sigiriya, Sri Lanka', 4, 25, 15000.00, 'WiFi, Swimming Pool, Restaurant, Parking, Spa', '2:00 PM', '12:00 PM'),
(4, 15, 'koggala ', 'galle ', 3, 2, 10000.00, 'wifi', '', ''),
(6, 17, 'tangalle hotel', 'tangalle', 4, 6, 10000.00, NULL, NULL, NULL),
(7, 21, 'panadura hills', 'panadura,srilanaka', 3, 7, 10000.00, NULL, NULL, NULL),
(8, 24, 'lahiru', 'akurassa', 1, 7, 10000.00, 'pool,rest room', '8.30am', '2.00pm'),
(9, 27, 'colombo 2 villa hotel', 'colombo2 ,maharagama', 3, 200, 10000.00, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `insurance_requests`
--

CREATE TABLE `insurance_requests` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `insurance_type` varchar(50) DEFAULT 'Standard Travel Insurance',
  `coverage_amount` decimal(10,2) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `insurance_requests`
--

INSERT INTO `insurance_requests` (`id`, `booking_id`, `insurance_type`, `coverage_amount`, `price`, `status`, `created_at`) VALUES
(1, 2, 'Standard', 183000.00, 1500.00, 'pending', '2026-07-15 21:33:12'),
(4, 6, 'Standard', 69000.00, 1500.00, 'pending', '2026-07-16 16:24:03'),
(20, 22, 'Premium', 3997000.00, 3500.00, 'pending', '2026-09-30 10:45:00'),
(22, 24, 'Standard', 380000.00, 1500.00, 'pending', '2026-10-04 17:47:51');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `destination` varchar(200) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `duration_nights` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `included_services` text DEFAULT NULL,
  `max_people` int(11) DEFAULT 10,
  `image_url` varchar(500) DEFAULT NULL,
  `package_code` varchar(20) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'General',
  `difficulty_level` enum('Easy','Medium','Hard') DEFAULT 'Easy',
  `meeting_point` varchar(255) DEFAULT NULL,
  `best_season` varchar(100) DEFAULT NULL,
  `languages` varchar(100) DEFAULT NULL,
  `highlights` text DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `weather_info` text DEFAULT NULL,
  `status` enum('active','inactive','archived') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `name`, `destination`, `duration_days`, `duration_nights`, `price`, `description`, `included_services`, `max_people`, `image_url`, `package_code`, `category`, `difficulty_level`, `meeting_point`, `best_season`, `languages`, `highlights`, `latitude`, `longitude`, `weather_info`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Ancient Kingdom Explorer', 'Sigiriya, Polonnaruwa, Dambulla', 6, 5, 45000.00, 'Explore the rich history of Sri Lanka\'s ancient capitals. Visit the iconic Sigiriya Rock Fortress, the ancient city of Polonnaruwa, and the Golden Temple of Dambulla.', 'Hotel Accommodation, Expert Guide, AC Transport, All Meals, Entrance Fees', 15, 'https://images.unsplash.com/photo-1580238053495-b972040f6ab9?w=400', 'PKG-001', 'Cultural', 'Easy', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 1, '2026-07-15 21:19:57', '2026-07-15 21:19:57'),
(2, 'Southern Beach Paradise', 'Galle, Mirissa, Bentota', 5, 4, 38000.00, 'Experience the stunning beaches of Sri Lanka\'s south coast. Enjoy whale watching in Mirissa, explore Galle Fort, and relax on golden sands.', 'Beach Resort, Guide, Transport, Breakfast, Dinner', 18, 'https://images.unsplash.com/photo-1582391125994-dab7f5b16b5e?w=400', 'PKG-002', 'Beach', 'Easy', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 1, '2026-07-15 21:19:57', '2026-07-15 21:19:57'),
(3, 'Hill Country Escape', 'Kandy, Nuwara Eliya, Ella', 7, 6, 55000.00, 'Journey through misty mountains and tea plantations. Visit the Temple of the Tooth, enjoy scenic train rides, and hike to Ella Rock.', 'Hotel, Guide, Train Pass, Tea Factory Tour, All Meals', 12, 'https://images.unsplash.com/photo-1501594907352-04cda38ebc29?w=400', 'PKG-003', 'Hill Country', 'Medium', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 1, '2026-07-15 21:19:57', '2026-07-15 21:19:57'),
(4, 'Wildlife Adventure', 'Yala, Udawalawe, Minneriya', 4, 3, 32000.00, 'Experience Sri Lanka\'s incredible wildlife. Spot leopards in Yala, elephants in Udawalawe, and enjoy jeep safaris.', 'Eco Lodge, Safari Jeep, Guide, Meals', 8, 'https://images.unsplash.com/photo-1549366021-9f761d450615?w=400', 'PKG-004', 'Wildlife', 'Medium', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 1, '2026-07-15 21:19:57', '2026-07-15 21:19:57'),
(5, 'Kandy Cultural Tour', 'Kandy, Peradeniya, Matale', 3, 2, 22000.00, 'Discover the cultural heart of Sri Lanka. Visit the Temple of the Sacred Tooth Relic, Royal Botanical Gardens, and spice gardens.', 'Hotel, Guide, Transport, Breakfast', 10, 'https://images.unsplash.com/photo-1560171382-8e69e0f7c794?w=400', 'PKG-005', 'Cultural', 'Easy', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 1, '2026-07-15 21:19:57', '2026-07-15 21:19:57'),
(6, 'Luxury Sri Lanka Tour', 'Colombo, Kandy, Nuwara Eliya, Galle', 10, 9, 150000.00, 'Experience the ultimate luxury tour of Sri Lanka. Stay at 5-star hotels and enjoy private tours.', '5-Star Hotels, Private Guide, Luxury Transport, All Meals', 6, 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400', 'PKG-201', 'Luxury', 'Easy', 'Colombo Airport', 'Jan-Dec', 'English, Sinhala', '5-Star Hotels, Private Tours', '6.9270', '79.8610', 'Warm 31°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(7, 'Honeymoon Special Package', 'Bentota, Mirissa, Kandy', 7, 6, 85000.00, 'A romantic honeymoon package with candlelight dinners and sunset cruises.', 'Luxury Resort, Romantic Dinner, Spa Treatments, Private Tours', 2, 'https://images.unsplash.com/photo-1519046904884-53103b34b206?w=400', 'PKG-202', 'Luxury', 'Easy', 'Colombo Airport', 'Jan-Dec', 'English, Sinhala', 'Romantic Getaway, Sunset Cruises', '6.4200', '79.9900', 'Sunny 31°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(8, 'Family Fun Package', 'Colombo, Bentota, Kandy, Sigiriya', 8, 7, 95000.00, 'A perfect family vacation with activities for all ages.', 'Family Hotels, Safari, Theme Parks, Guide, Meals', 8, 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=400', 'PKG-203', 'Family', 'Easy', 'Colombo Airport', 'Jan-Dec', 'English, Sinhala', 'Family Fun, Wildlife, Culture', '6.9270', '79.8610', 'Warm 31°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(9, 'Corporate Retreat Package', 'Negombo, Kandy, Nuwara Eliya', 5, 4, 65000.00, 'A corporate retreat with team building activities and conferences.', 'Business Hotel, Conference Rooms, Team Building Activities, Meals', 20, 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=400', 'PKG-204', 'Business', 'Easy', 'Negombo Beach', 'Jan-Dec', 'English, Sinhala', 'Team Building, Conferences', '7.2086', '79.8362', 'Sunny 32°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(10, 'Wellness & Yoga Retreat', 'Bentota, Mirissa, Kandy', 6, 5, 72000.00, 'A rejuvenating wellness retreat with yoga and meditation.', 'Wellness Resort, Yoga Instructor, Ayurvedic Treatments, Organic Meals', 10, 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=400', 'PKG-205', 'Wellness', 'Easy', 'Colombo Airport', 'Jan-Dec', 'English, Sinhala', 'Yoga, Meditation, Ayurveda', '6.4200', '79.9900', 'Sunny 31°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(11, 'Extreme Adventure Tour', 'Kithulgala, Ella, Knuckles', 7, 6, 68000.00, 'Experience white water rafting, rock climbing, and extreme trekking.', 'Adventure Lodge, Safety Gear, Guides, Meals', 8, 'https://images.unsplash.com/photo-1530549387789-4c1017266635?w=400', 'PKG-206', 'Adventure', 'Hard', 'Kithulgala Town', 'Jan-Apr', 'English, Sinhala', 'White Water Rafting, Climbing', '6.9500', '80.4000', 'Warm 28°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(12, 'Mountain Biking Expedition', 'Knuckles, Kandy, Matale', 5, 4, 45000.00, 'Mountain bike through the stunning Knuckles Mountain Range.', 'Mountain Lodge, Bikes, Guide, Meals', 6, 'https://images.unsplash.com/photo-1571066811602-716837d681de?w=400', 'PKG-207', 'Adventure', 'Hard', 'Kandy City Center', 'Jan-Aug', 'English, Sinhala', 'Mountain Biking, Trails', '7.4500', '80.8000', 'Cool 22°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(13, 'Rock Climbing Adventure', 'Sigiriya, Pidurangala, Knuckles', 4, 3, 38000.00, 'Experience rock climbing on Sri Lanka\'s iconic rock formations.', 'Eco Lodge, Climbing Gear, Guide, Meals', 6, 'https://images.unsplash.com/photo-1522163182402-834f871fd851?w=400', 'PKG-208', 'Adventure', 'Hard', 'Sigiriya Museum', 'Jan-Dec', 'English, Sinhala', 'Rock Climbing, Training', '7.9569', '80.7597', 'Hot 32°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(14, 'Kayaking Expedition', 'Bentota, Madu River, Galle', 4, 3, 35000.00, 'Kayak through the beautiful waterways of Bentota and Madu River.', 'Beach Resort, Kayaks, Guide, Meals', 8, 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=400', 'PKG-209', 'Adventure', 'Medium', 'Bentota Beach', 'Nov-Apr', 'English, Sinhala', 'Kayaking, Mangroves', '6.4200', '79.9900', 'Sunny 31°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(15, 'Canyoning Experience', 'Kithulgala, Kelani River, Colombo', 3, 2, 28000.00, 'Experience canyoning through waterfalls and natural pools.', 'Adventure Lodge, Safety Gear, Guide, Meals', 6, 'https://images.unsplash.com/photo-1603162525938-1aa5df1479f4?w=400', 'PKG-210', 'Adventure', 'Hard', 'Kithulgala Town', 'Jan-Apr', 'English, Sinhala', 'Canyoning, Waterfalls', '6.9500', '80.4000', 'Warm 28°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(16, 'Buddhist Pilgrimage Tour', 'Anuradhapura, Polonnaruwa, Kandy', 8, 7, 55000.00, 'Visit the most sacred Buddhist sites in Sri Lanka.', 'Hotels, Guide, Transport, Temple Donations, Meals', 12, 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=400', 'PKG-211', 'Spiritual', 'Easy', 'Colombo Airport', 'Jan-Dec', 'English, Sinhala', 'Sacred Sites, Buddhism', '8.3114', '80.4160', 'Hot 33°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(17, 'Meditation & Mindfulness Retreat', 'Kandy, Gampola, Peradeniya', 5, 4, 42000.00, 'Learn meditation and mindfulness techniques.', 'Meditation Center, Qualified Teacher, Organic Meals', 10, 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=400', 'PKG-212', 'Wellness', 'Easy', 'Kandy City Center', 'Jan-Dec', 'English, Sinhala', 'Meditation, Mindfulness', '7.2906', '80.6336', 'Warm 28°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(18, 'Ayurveda Detox Program', 'Bentota, Aluthgama, Beruwala', 7, 6, 58000.00, 'A comprehensive Ayurvedic detox program.', 'Ayurveda Center, Doctor Consultation, Treatments, Meals', 8, 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=400', 'PKG-213', 'Wellness', 'Easy', 'Bentota Beach', 'Jan-Dec', 'English, Sinhala', 'Ayurveda, Detox', '6.4200', '79.9900', 'Sunny 31°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(19, 'Sri Pada Pilgrimage', 'Adams Peak, Nallathanniya, Hatton', 4, 3, 28000.00, 'A sacred journey to Adams Peak. Witness the holy footprint and sunrise.', 'Rest Houses, Guide, Meals', 10, 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=400', 'PKG-214', 'Spiritual', 'Hard', 'Nallathanniya Base', 'Dec-Apr', 'English, Sinhala', 'Sacred Footprint, Sunrise', '6.8093', '80.4999', 'Cold 15°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(20, 'Yoga Teacher Training', 'Galle, Unawatuna, Talpe', 14, 13, 95000.00, 'Become a certified yoga teacher with 200-hour training.', 'Yoga Center, Accommodation, Meals, Certification', 12, 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=400', 'PKG-215', 'Wellness', 'Medium', 'Unawatuna Beach', 'Jan-Dec', 'English, Sinhala', 'Yoga Teacher Training', '6.0167', '80.2500', 'Sunny 31°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(21, 'Eco-Tourism Experience', 'Sinharaja, Deniyaya, Kudawa', 5, 4, 42000.00, 'Experience eco-tourism in Sinharaja Rainforest.', 'Eco Lodge, Guide, Meals, Carbon Offset', 8, 'https://images.unsplash.com/photo-1549366021-9f761d450615?w=400', 'PKG-216', 'Eco', 'Medium', 'Kudawa Entrance', 'Jan-Apr', 'English, Sinhala', 'Rainforest, Eco Tourism', '6.4000', '80.4667', 'Warm 28°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(22, 'Organic Farm Stay', 'Kandy, Matale, Gampola', 4, 3, 32000.00, 'Stay at an organic farm and learn about sustainable agriculture.', 'Farm Stay, Organic Meals, Farming Experience', 8, 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?w=400', 'PKG-217', 'Eco', 'Easy', 'Matale Town', 'Jan-Dec', 'English, Sinhala', 'Organic Farming, Sustainability', '7.4667', '80.6167', 'Warm 28°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(23, 'Village Homestay Experience', 'Habarana, Minneriya, Polonnaruwa', 5, 4, 28000.00, 'Experience authentic Sri Lankan village life.', 'Village Homestay, Meals, Cultural Activities', 6, 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=400', 'PKG-218', 'Cultural', 'Easy', 'Habarana Town', 'Jan-Dec', 'English, Sinhala', 'Village Life, Culture', '8.0333', '80.7500', 'Hot 33°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(24, 'Whale & Dolphin Conservation', 'Mirissa, Weligama, Galle', 6, 5, 48000.00, 'Participate in whale and dolphin conservation efforts.', 'Beach Resort, Conservation Activities, Guide, Meals', 6, 'https://images.unsplash.com/photo-1582391125994-dab7f5b16b5e?w=400', 'PKG-219', 'Eco', 'Easy', 'Mirissa Beach', 'Nov-Apr', 'English, Sinhala', 'Whale Watching, Conservation', '5.9470', '80.4600', 'Sunny 30°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(25, 'Reforestation Project', 'Knuckles, Kandy, Matale', 7, 6, 38000.00, 'Join a reforestation project in Knuckles Mountain Range.', 'Camping, Meals, Conservation Activities', 10, 'https://images.unsplash.com/photo-1501594907352-04cda38ebc29?w=400', 'PKG-220', 'Eco', 'Hard', 'Kandy City Center', 'Jan-Aug', 'English, Sinhala', 'Reforestation, Conservation', '7.4500', '80.8000', 'Cool 22°C', 'active', 1, '2026-07-16 06:05:12', '2026-07-16 06:05:12'),
(26, 'Negombo Lagoon Cruise', 'Negombo, Chilaw, Colombo', 2, 1, 16000.00, 'Enjoy a relaxing sunset cruise on the Negombo lagoon with stunning views and local snacks.', 'Boat Cruise, Snacks, Guide, Transport', 12, 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=400', 'PKG-311', 'Adventure', 'Easy', 'Negombo Beach', 'Nov-Apr', 'English, Sinhala', 'Lagoon Cruise, Sunset Views', '7.2086', '79.8362', 'Sunny 32°C', 'active', 1, '2026-07-16 06:20:54', '2026-07-16 06:20:54'),
(27, 'Morning Yoga on Beach', 'Negombo, Bentota, Galle', 3, 2, 22000.00, 'Start your day with peaceful sunrise yoga sessions on the beach. Includes meditation and healthy breakfast.', 'Yoga Sessions, Accommodation, Meals, Transport', 10, 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=400', 'PKG-312', 'Wellness', 'Easy', 'Negombo Beach', 'Jan-Dec', 'English, Sinhala', 'Yoga, Meditation, Sunrise', '7.2086', '79.8362', 'Sunny 32°C', 'active', 1, '2026-07-16 06:20:54', '2026-07-16 06:20:54'),
(28, 'Bird Watching at Muthurajawela', 'Muthurajawela, Negombo, Colombo', 2, 1, 14000.00, 'Explore the Muthurajawela marshlands and spot a variety of migratory and resident birds.', 'Boat Ride, Guide, Transport, Meals', 8, 'https://images.unsplash.com/photo-1549366021-9f761d450615?w=400', 'PKG-313', 'Wildlife', 'Easy', 'Negombo Town', 'Jan-Dec', 'English, Sinhala', 'Bird Watching, Marshlands', '7.1500', '79.8667', 'Warm 31°C', 'active', 1, '2026-07-16 06:20:54', '2026-07-16 06:20:54'),
(29, 'Private Beach Picnic', 'Kalutara, Beruwala, Bentota', 2, 1, 17000.00, 'Enjoy a private beach picnic with a gourmet basket, perfect for couples and families.', 'Private Beach, Picnic Basket, Transport, Guide', 6, 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=400', 'PKG-314', 'Romantic', 'Easy', 'Kalutara Beach', 'Nov-Apr', 'English, Sinhala', 'Private Beach, Picnic', '6.5833', '79.9667', 'Sunny 31°C', 'active', 1, '2026-07-16 06:20:54', '2026-07-16 06:20:54'),
(30, 'Coconut Oil Making Workshop', 'Chilaw, Marawila, Negombo', 1, 0, 9000.00, 'Learn the traditional process of making pure coconut oil from fresh coconuts.', 'Workshop, Materials, Guide, Transport', 10, 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=400', 'PKG-315', 'Cultural', 'Easy', 'Chilaw Town', 'Jan-Dec', 'English, Sinhala', 'Coconut Oil, Traditional Craft', '7.5833', '79.8000', 'Sunny 31°C', 'active', 1, '2026-07-16 06:20:54', '2026-07-16 06:20:54'),
(31, 'Batik Making Workshop', 'Galle, Matara, Colombo', 2, 1, 15000.00, 'Learn the art of traditional Sri Lankan batik making and create your own masterpiece.', 'Workshop, Materials, Guide, Transport, Meals', 10, 'https://images.unsplash.com/photo-1582391125994-dab7f5b16b5e?w=400', 'PKG-316', 'Cultural', 'Easy', 'Galle Town', 'Jan-Dec', 'English, Sinhala', 'Batik Making, Art', '6.0333', '80.2167', 'Partly Cloudy 30°C', 'active', 1, '2026-07-16 06:20:54', '2026-07-16 06:20:54'),
(32, 'Sri Lankan Street Food Tour', 'Colombo, Pettah, Galle Face', 1, 0, 10000.00, 'Taste the best Sri Lankan street food from the bustling streets of Colombo.', 'Food Tastings, Guide, Transport', 8, 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=400', 'PKG-317', 'Culinary', 'Easy', 'Pettah Market', 'Jan-Dec', 'English, Sinhala', 'Street Food, Local Cuisine', '6.9370', '79.8610', 'Warm 31°C', 'active', 1, '2026-07-16 06:20:54', '2026-07-16 06:20:54'),
(33, 'Kelaniya River Rafting', 'Kithulgala, Kelani River, Colombo', 2, 1, 16000.00, 'Enjoy white water rafting on the Kelani River with beautiful scenery and thrilling rapids.', 'Rafting Equipment, Guide, Transport, Meals', 8, 'https://images.unsplash.com/photo-1530549387789-4c1017266635?w=400', 'PKG-318', 'Adventure', 'Hard', 'Kithulgala Town', 'Jan-Apr', 'English, Sinhala', 'White Water Rafting', '6.9500', '80.4000', 'Warm 28°C', 'active', 1, '2026-07-16 06:20:54', '2026-07-16 06:20:54'),
(34, 'Spice Garden Experience', 'Matale, Kandy, Gampola', 2, 1, 13000.00, 'Visit a traditional spice garden and learn about spices, herbs, and traditional medicine.', 'Spice Garden Tour, Guide, Transport, Meals', 15, 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=400', 'PKG-319', 'Cultural', 'Easy', 'Matale Town', 'Jan-Dec', 'English, Sinhala', 'Spice Garden, Herbs', '7.4667', '80.6167', 'Warm 28°C', 'active', 1, '2026-07-16 06:20:54', '2026-07-16 06:20:54'),
(35, 'Deep Sea Fishing Day Trip', 'Mirissa, Weligama, Galle', 1, 0, 12000.00, 'Enjoy a day of deep sea fishing in the Indian Ocean with experienced local fishermen.', 'Fishing Boat, Equipment, Guide, Meals', 6, 'https://images.unsplash.com/photo-1582391125994-dab7f5b16b5e?w=400', 'PKG-320', 'Adventure', 'Easy', 'Mirissa Beach', 'Nov-Apr', 'English, Sinhala', 'Deep Sea Fishing', '5.9470', '80.4600', 'Sunny 30°C', 'active', 1, '2026-07-16 06:20:54', '2026-07-16 06:20:54'),
(36, 'Kataragama Pilgrimage', 'Kataragama, Tissamaharama, Yala', 3, 2, 16000.00, '1234', '', 20, 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=600', 'PKG-032', 'General', 'Easy', 'Kataragama Town', 'January - September', 'English, Sinhala', '', NULL, NULL, NULL, 'active', 8, '2026-07-16 16:41:22', '2026-07-16 16:41:22');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `transaction_id` varchar(50) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) NOT NULL,
  `payment_details` text DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'completed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `transaction_id`, `booking_id`, `amount`, `payment_method`, `payment_details`, `payment_date`, `status`) VALUES
(1, 'TXN202610041913', 24, 190000.00, 'Online Wallet', '{\"method\":\"Online Wallet\"}', '2026-10-04 17:51:36', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `promo_codes`
--

CREATE TABLE `promo_codes` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percentage','fixed') DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `valid_from` date NOT NULL,
  `valid_to` date NOT NULL,
  `usage_limit` int(11) DEFAULT 1,
  `used_count` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promo_codes`
--

INSERT INTO `promo_codes` (`id`, `code`, `discount_type`, `discount_value`, `min_order_amount`, `max_discount`, `valid_from`, `valid_to`, `usage_limit`, `used_count`, `status`, `created_at`) VALUES
(1, 'WELCOME10', 'percentage', 10.00, 5000.00, 5000.00, '2026-07-16', '2026-08-15', 50, 0, 'active', '2026-07-15 21:19:57'),
(2, 'SUMMER20', 'percentage', 20.00, 10000.00, 10000.00, '2026-07-16', '2026-08-15', 25, 0, 'active', '2026-07-15 21:19:57'),
(3, 'FIXED500', 'fixed', 500.00, 2000.00, NULL, '2026-07-16', '2026-08-15', 100, 0, 'active', '2026-07-15 21:19:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','customer','guide','hotel') DEFAULT 'customer',
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `profile_image`, `status`, `created_at`, `updated_at`) VALUES
(1, 'System Administrator', 'admin@paradise.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0712345678', 'admin', NULL, 'active', '2026-07-15 21:19:56', '2026-07-15 21:19:56'),
(2, 'Nimal Perera', 'nimal@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0771234567', 'customer', NULL, 'active', '2026-07-15 21:19:56', '2026-07-15 21:19:56'),
(4, 'Sigiriya Rock View Hotel', 'info@sigiriyarock.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0662345678', 'hotel', NULL, 'active', '2026-07-15 21:19:56', '2026-07-15 21:19:56'),
(5, 'nadun poorna', 'nadunpoorna98@gmail.com', '$2y$10$4hOSg86OCrVN9Z45YqmOhe2y6wJ0nMAV1Lm86TgyTKhWgFTDfnk6O', '0710147969', 'customer', NULL, 'active', '2026-07-15 21:23:47', '2026-07-15 21:23:47'),
(8, 'silva', 'silva123@gmail.com', '$2y$10$evvlgj1VmY3iPtMRJdLQke0xyfRorHuzmSzCrbLCoDYcu.gMMXihW', '0712334234', 'admin', NULL, 'active', '2026-07-15 21:28:34', '2026-07-15 21:28:34'),
(9, 'sithum', 'sithum123@gmail.com', '$2y$10$239EcS0DfmipOBSOXCrXvunNc6ILdLyV7vLosr58HM2Zo.ldUTmhC', '0761723130', 'customer', NULL, 'active', '2026-07-16 05:49:15', '2026-07-16 05:49:15'),
(10, 'kamal', 'kamal123@gmail.com', '$2y$10$TrUVZ6pSWFmUXFF3GboXTuR3f29SEZ6AWFhQJgQ/e8qNSzWNxPCf.', '0701004534', 'customer', NULL, 'active', '2026-07-16 16:14:43', '2026-07-16 16:14:43'),
(11, 'nimal', 'nimal123@gmail.com', '$2y$10$7wDFpA9wlWPzJuOUayMh5uY9P4JTW4E8dD/731TADFKvSA2InDrGC', '0761723130', 'guide', NULL, 'active', '2026-07-17 17:53:35', '2026-07-17 17:53:35'),
(13, 'pubudu', 'pubudu123@gmail.com', '$2y$10$/SE6q5JxAPREehNTAsbMPO4mQDtZczVnMTixi5Ef/JaBzU89QATjO', '0778765456', 'guide', NULL, 'active', '2026-07-17 18:06:37', '2026-07-17 18:06:37'),
(15, 'koggala ', 'koggala123@gmailcom', '$2y$10$eFvAKJttUsfHCUyqqvIRIec3cCNa2Vsh39DBZbeqEK.kj0MSLi8B2', '0787656543', 'hotel', NULL, 'active', '2026-07-17 18:10:01', '2026-07-17 18:10:01'),
(17, 'abc', 'abc123@gmail.com', '$2y$10$F1JVC0gdVhHwnzB4Qxz0AOJkX3fvPgdsoOfnPINTXh9CC3lj/Jgba', '0767325518', 'hotel', NULL, 'active', '2026-07-20 06:43:06', '2026-07-20 06:43:06'),
(18, 'admin', 'admin123@gmail.com', '$2y$10$Wrz8JeF0zhFGfd8agwk1m.nQ516Wo7n9L6doCBezmdyF7nfbsj7CK', '0710147969', 'admin', NULL, 'active', '2026-07-20 06:45:14', '2026-07-20 06:45:14'),
(19, 'asela', 'asela12@gmail.com', '$2y$10$JwYLEUCscQLyZ7T1zN7reObKIe2pqFss6lDZmrB0E8gHVA/SmoqNi', '0712536145', 'admin', NULL, 'active', '2026-09-16 18:53:54', '2026-09-16 18:53:54'),
(21, 'gamage', 'gamage@gmail.com', '$2y$10$Cjt0aTb7kSpQVIxuSyuCreuguT23xDCmKawj0oGn4ZsXloTse5.C2', '0712536145', 'hotel', NULL, 'active', '2026-09-30 10:57:26', '2026-09-30 10:57:26'),
(22, 'chamoth', 'chamoth@gmail.com', '$2y$10$d0MS40Q9ZVqYU4sf2a2xR.wMUG5pbeOFzfGZQNK06B6xtkfP2Uu7W', '0712536145', 'customer', NULL, 'active', '2026-10-04 17:27:47', '2026-10-04 17:27:47'),
(23, 'agith', 'agith@gmail.com', '$2y$10$3bBD/cm0iIUzUf5i2NL0reFHj8yO20utafoSRFTbrozqBHGczNBw.', '0787676543', 'guide', NULL, 'active', '2026-10-04 17:29:42', '2026-10-04 17:29:42'),
(24, 'lahiru', 'lahiru@gmail.com', '$2y$10$XwM5ZaM01H1tsBl/gLipru/ugITNTwDi5RnpPkAUhXi3k5yNrdyFW', '0753625148', 'hotel', NULL, 'active', '2026-10-04 17:31:27', '2026-10-04 17:31:27'),
(25, 'Yehan Chiranjeewa Fernando', 'Chiranjeewayehan@gmail.com', '$2y$10$C8ZAgO8CI2FvvU4BvQ6M5emA62a79Ui0P/J0mpUHApszm7KYW1j9a', '0783904197', 'customer', NULL, 'active', '2026-10-05 14:45:27', '2026-10-05 14:45:27'),
(26, 'supun', 'supun12@gmail.com', '$2y$10$fgidm.f0xQMULrjBEGh/jOJEckBh9MyL6bIrlGhxBpqkDjwAtXGEm', '0712536145', 'customer', NULL, 'active', '2026-10-06 02:40:15', '2026-10-06 02:40:15'),
(27, 'dilshan', 'dilshan11@gmail.com', '$2y$10$NvYbyRIKCQu/9i8B04uO4OETd6efGqjLoJgNvKeV3ik4yVHJSEX7a', '0745456852', 'hotel', NULL, 'active', '2026-10-06 02:43:22', '2026-10-06 02:43:22');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `vehicle_code` varchar(20) NOT NULL,
  `vehicle_name` varchar(100) NOT NULL,
  `vehicle_type` enum('Car','SUV','Van','Bus','Motorbike','Tuk Tuk','Luxury Car') NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `seat_count` int(11) DEFAULT 4,
  `price_per_day` decimal(10,2) NOT NULL,
  `price_per_km` decimal(10,2) DEFAULT 0.00,
  `driver_name` varchar(100) DEFAULT NULL,
  `driver_phone` varchar(20) DEFAULT NULL,
  `driver_license` varchar(50) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `vehicle_url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `features` text DEFAULT NULL,
  `status` enum('available','booked','maintenance','inactive') DEFAULT 'available',
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `vehicle_code`, `vehicle_name`, `vehicle_type`, `brand`, `model`, `year`, `color`, `seat_count`, `price_per_day`, `price_per_km`, `driver_name`, `driver_phone`, `driver_license`, `location`, `image_url`, `vehicle_url`, `description`, `features`, `status`, `rating`, `total_reviews`, `created_at`, `updated_at`) VALUES
(7, 'VEH-007', 'Mitsubishi Montero', 'SUV', 'Mitsubishi', 'Montero 2020', 2020, 'White', 7, 22000.00, 140.00, 'Pradeep Silva', '0777890123', 'B7890123', 'Nuwara Eliya', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=400', NULL, 'Powerful SUV for hill country and off-road trips', 'AC, 4WD, GPS, 7 Seats, Luggage Carrier', 'booked', 4.70, 41, '2026-09-16 19:12:07', '2026-09-29 15:22:35'),
(31, 'BCH-1234', 'BMW 520', 'Car', 'BMW', 'BMW 2020', 2020, 'white', 6, 1500.00, 2.00, 'Gamage', '0752323456', 'B2356789', 'panadura,srilanaka', 'https://th.bing.com/th/id/OIP.NkVrwdf5IJun0Q8CJrEBfwHaEJ?w=374&h=180&c=7&r=0&o=7&pid=1.7&rm=3', NULL, 'best service', 'all funtions', 'booked', 0.00, 0, '2026-10-03 06:16:55', '2026-10-04 17:54:30'),
(33, 'AAC-0879', 'threeweel', 'Tuk Tuk', 'bajaj', '2019', 2020, 'red', 4, 1000.00, 0.00, 'vihaga', '0711667656', 'B12435678', 'monaragala', 'https://th.bing.com/th/id/OIP.Oqaib_vMKeBMkHgIOXGGFgHaD2?w=327&h=180&c=7&r=0&o=7&pid=1.7&rm=3', NULL, 'full option', 'ac,bt', 'booked', 0.00, 0, '2026-10-04 17:46:38', '2026-10-04 17:47:51'),
(34, 'nc', 'bus', 'Bus', 'bus', 'bus', 2020, 'white', 54, 2.00, 1000.00, 'malaka', '0752545852', 'B565433456', 'galla', 'https://thumbs.dreamstime.com/b/sri-lankan-colourful-leyland-busses-lanka-llivery-67942934.jpg', NULL, '', 'ac', 'available', 0.00, 0, '2026-10-05 16:07:50', '2026-10-05 16:07:50'),
(36, 'NC-5654', 'dolfing', 'Car', 'toyota', 'dolping 2019', 2019, 'white', 4, 2.00, 0.00, 'prmoth', '0774545852', 'B34544323', 'galla', 'https://th.bing.com/th/id/OIP.0N2bIZjoDoa9Ba5YxA5_rgHaDm?w=331&h=169&c=7&r=0&o=7&pid=1.7&rm=3', '', 'special', 'ac', 'available', 0.00, 0, '2026-10-06 03:01:15', '2026-10-06 03:01:15');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_bookings`
--

CREATE TABLE `vehicle_bookings` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `pickup_location` varchar(255) DEFAULT NULL,
  `dropoff_location` varchar(255) DEFAULT NULL,
  `pickup_date` datetime DEFAULT NULL,
  `dropoff_date` datetime DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `vehicle_id` int(11) DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `driver_phone` varchar(20) DEFAULT NULL,
  `days` int(11) DEFAULT 1,
  `pickup_time` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_bookings`
--

INSERT INTO `vehicle_bookings` (`id`, `booking_id`, `vehicle_type`, `pickup_location`, `dropoff_location`, `pickup_date`, `dropoff_date`, `total_price`, `status`, `created_at`, `vehicle_id`, `driver_name`, `driver_phone`, `days`, `pickup_time`, `notes`) VALUES
(1, 2, 'Car', 'Ancient Kingdom Explorer', NULL, '2026-07-24 03:03:00', NULL, 9150.00, 'pending', '2026-07-15 21:33:12', NULL, NULL, NULL, 1, NULL, NULL),
(5, 6, 'Car', 'Morning Yoga on Beach - Negombo, Bentota, Galle', NULL, '2026-07-01 21:53:00', NULL, 3450.00, 'pending', '2026-07-16 16:24:03', NULL, NULL, NULL, 1, NULL, NULL),
(21, 22, 'weel (Tuk Tuk)', 'Galle road, malabe', '', '2026-09-30 09:00:00', '2026-10-01 00:00:00', 4400.00, 'pending', '2026-09-30 10:45:00', 28, 'kaveesha', '0765654345', 1, '09:00', 'special'),
(23, 24, 'threeweel (Tuk Tuk)', 'monaragala', 'mathara', '2026-10-04 09:00:00', '2026-10-08 00:00:00', 4000.00, 'pending', '2026-10-04 17:47:51', 33, 'vihaga', '0711667656', 4, '09:00', 'best hire');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_no` (`booking_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `guide_id` (`guide_id`),
  ADD KEY `hotel_id` (`hotel_id`);

--
-- Indexes for table `customer_profiles`
--
ALTER TABLE `customer_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `guide_profiles`
--
ALTER TABLE `guide_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `hotel_bookings`
--
ALTER TABLE `hotel_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `hotel_id` (`hotel_id`);

--
-- Indexes for table `hotel_profiles`
--
ALTER TABLE `hotel_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `insurance_requests`
--
ALTER TABLE `insurance_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `package_code` (`package_code`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicle_code` (`vehicle_code`);

--
-- Indexes for table `vehicle_bookings`
--
ALTER TABLE `vehicle_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `customer_profiles`
--
ALTER TABLE `customer_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `guide_profiles`
--
ALTER TABLE `guide_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `hotel_bookings`
--
ALTER TABLE `hotel_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `hotel_profiles`
--
ALTER TABLE `hotel_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `insurance_requests`
--
ALTER TABLE `insurance_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `vehicle_bookings`
--
ALTER TABLE `vehicle_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`guide_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bookings_ibfk_4` FOREIGN KEY (`hotel_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customer_profiles`
--
ALTER TABLE `customer_profiles`
  ADD CONSTRAINT `customer_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `guide_profiles`
--
ALTER TABLE `guide_profiles`
  ADD CONSTRAINT `guide_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hotel_bookings`
--
ALTER TABLE `hotel_bookings`
  ADD CONSTRAINT `hotel_bookings_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hotel_bookings_ibfk_2` FOREIGN KEY (`hotel_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hotel_profiles`
--
ALTER TABLE `hotel_profiles`
  ADD CONSTRAINT `hotel_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `insurance_requests`
--
ALTER TABLE `insurance_requests`
  ADD CONSTRAINT `insurance_requests_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `packages`
--
ALTER TABLE `packages`
  ADD CONSTRAINT `packages_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `vehicle_bookings`
--
ALTER TABLE `vehicle_bookings`
  ADD CONSTRAINT `vehicle_bookings_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
