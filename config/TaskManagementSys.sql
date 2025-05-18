-- Create database
CREATE DATABASE IF NOT EXISTS TaskManagementSystem;

-- Use the database
USE TaskManagementSystem;

-- Create Users table
CREATE TABLE Users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(255) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Email VARCHAR(255) NOT NULL UNIQUE,
    Role VARCHAR(5) NOT NULL,
    FullName VARCHAR(255) NOT NULL,
    PhoneNumber VARCHAR(10),
    Avatar VARCHAR(255) DEFAULT '/images/default-avatar.png'
);

-- Create Project table
CREATE TABLE Project (
    ProjectID INT AUTO_INCREMENT PRIMARY KEY,
    ProjectName VARCHAR(255) NOT NULL UNIQUE,
    ProjectDescription TEXT,
    CreatedBy INT NOT NULL,
    StartDate DATETIME,
    EndDate DATETIME,
    BackgroundUrl VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (CreatedBy) REFERENCES Users(UserID)
);

-- Create ProjectMembers table
CREATE TABLE ProjectMembers (
    ProjectMembersID INT AUTO_INCREMENT PRIMARY KEY,
    ProjectID INT NOT NULL,
    UserID INT NOT NULL,
    RoleInProject ENUM('người sở hữu', 'thành viên'),
    JoinedAt DATETIME NOT NULL,
    FOREIGN KEY (ProjectID) REFERENCES Project(ProjectID),
    FOREIGN KEY (UserID) REFERENCES Users(UserID)
);

-- Create TaskStatus table
CREATE TABLE TaskStatus (
    TaskStatusID INT AUTO_INCREMENT PRIMARY KEY,
    StatusName VARCHAR(50) NOT NULL UNIQUE 
);

-- Create Task table
CREATE TABLE Task (
    TaskID INT AUTO_INCREMENT PRIMARY KEY,
    TaskTitle VARCHAR(255) NOT NULL UNIQUE,
    TaskDescription TEXT,
    TaskStatusID INT NOT NULL,
    Priority ENUM('Thấp', 'Trung Bình', 'Cao', 'Khẩn cấp'),
    StartDate DATETIME,
    EndDate DATETIME,
    ProjectID INT NOT NULL,
    ParentTaskID INT,
    FOREIGN KEY (TaskStatusID) REFERENCES TaskStatus(TaskStatusID),
    FOREIGN KEY (ProjectID) REFERENCES Project(ProjectID),
    FOREIGN KEY (ParentTaskID) REFERENCES Task(TaskID)
);

-- Create TaskAssignment table
CREATE TABLE TaskAssignment (
    AssignmentID INT AUTO_INCREMENT PRIMARY KEY,
    TaskID INT NOT NULL,
    UserID INT NOT NULL,
    AssignedAt DATETIME,
    AssignedBy INT NOT NULL,
    FOREIGN KEY (TaskID) REFERENCES Task(TaskID),
    FOREIGN KEY (UserID) REFERENCES Users(UserID),
    FOREIGN KEY (AssignedBy) REFERENCES Users(UserID)
);

-- Create Attachments table
CREATE TABLE Attachments (
    AttachmentID INT AUTO_INCREMENT PRIMARY KEY,
    TaskID INT NOT NULL,
    FileName VARCHAR(255) NOT NULL UNIQUE,
    FileUrl TEXT NOT NULL,
    FileType VARCHAR(50),
    FileSize INT NOT NULL,
    UploadedBy INT NOT NULL,
    UploadedAt DATETIME NOT NULL,
    FOREIGN KEY (TaskID) REFERENCES Task(TaskID),
    FOREIGN KEY (UploadedBy) REFERENCES Users(UserID)
);

-- Create TaskStatusHistory table
CREATE TABLE TaskStatusHistory (
    TaskStatusHistoryID INT AUTO_INCREMENT PRIMARY KEY,
    TaskID INT NOT NULL,
    OldStatusID INT NOT NULL,
    NewStatusID INT NOT NULL,
    ChangedBy INT NOT NULL,
    ChangedAt DATETIME NOT NULL,
    FOREIGN KEY (TaskID) REFERENCES Task(TaskID),
    FOREIGN KEY (OldStatusID) REFERENCES TaskStatus(TaskStatusID),
    FOREIGN KEY (NewStatusID) REFERENCES TaskStatus(TaskStatusID),
    FOREIGN KEY (ChangedBy) REFERENCES Users(UserID)
);

ALTER TABLE TaskAssignment
ADD CONSTRAINT uc_TaskUser UNIQUE (TaskID, UserID);

-- Insert sample data
INSERT INTO TaskStatus (TaskStatusID, StatusName) VALUES
(1, 'Cần làm'),
(2, 'Đang làm'),
(3, 'Đã làm');

INSERT INTO Users (Username, Password, Email, Role, FullName, PhoneNumber, Avatar) VALUES
('admin', MD5('admin123'), 'admin@example.com', 'ADMIN', 'Nguyễn Quản Trị', '0901234567', '/public/images/admin.png'),
('manager',MD5('manager123'), 'manager@example.com', 'USER', 'Trần Quản Lý', '0912345678', '/public/images/manager.png'),
('dev1', MD5('dev123'), 'dev1@example.com', 'USER', 'Lê Phát Triển', '0923456789', '/public/images/dev1.png'),
('dev2', MD5('dev123'), 'dev2@example.com', 'USER', 'Phạm Lập Trình','0934567890', '/public/images/dev2.png'),
('tester', MD5('tester123'), 'tester@example.com', 'USER', 'Hoàng Kiểm Thử', '0945678901', '/public/images/tester.png');

-- Project table (không còn CreatedBy = 1)
INSERT INTO Project (ProjectName, ProjectDescription, CreatedBy, StartDate, EndDate, BackgroundUrl) VALUES
('Hệ thống quản lý thông tin', 'Xây dựng hệ thống quản lý thông tin cho trường học', 2, '2023-01-10 08:00:00', '2023-06-30 17:00:00', "/public/images/data-management-bg.png"), -- manager
('Website bán hàng trực tuyến', 'Phát triển website bán hàng trực tuyến với đầy đủ tính năng', 3, '2023-02-15 08:00:00', '2023-08-15 17:00:00', "/public/images/webshop-bg.png"), -- dev1
('Ứng dụng di động đặt đồ ăn', 'Xây dựng ứng dụng đặt đồ ăn trên di động', 4, '2023-03-01 08:00:00', '2023-09-30 17:00:00', "/public/images/food-delivery-app-bg.png"), -- dev2
('Hệ thống quản lý nhân sự', 'Phát triển hệ thống quản lý nhân sự cho doanh nghiệp', 5, '2023-04-10 08:00:00', '2023-10-31 17:00:00', "/public/images/hr-managment-sys-bg.png"), -- tester
('Ứng dụng học tập trực tuyến', 'Xây dựng nền tảng học tập trực tuyến', 2, '2023-05-15 08:00:00', '2023-12-15 17:00:00', "/public/images/elearning-app-bg.png"); -- manager

-- ProjectMembers table (không còn user 1, chỉ 2,3,4,5)
INSERT INTO ProjectMembers (ProjectID, UserID, RoleInProject, JoinedAt) VALUES
(1, 2, 'người sở hữu', '2023-01-10 08:00:00'), -- manager là chủ project 1
(1, 3, 'thành viên', '2023-01-11 09:00:00'),
(1, 4, 'thành viên', '2023-01-12 10:00:00'),
(2, 3, 'người sở hữu', '2023-02-15 08:00:00'), -- dev1 là chủ project 2
(2, 2, 'thành viên', '2023-02-16 09:00:00'),
(2, 4, 'thành viên', '2023-02-17 10:00:00'),
(3, 4, 'người sở hữu', '2023-03-01 08:00:00'), -- dev2 là chủ project 3
(3, 2, 'thành viên', '2023-03-02 09:00:00'),
(3, 5, 'thành viên', '2023-03-03 10:00:00'),
(4, 5, 'người sở hữu', '2023-04-10 08:00:00'), -- tester là chủ project 4
(4, 2, 'thành viên', '2023-04-11 09:00:00'),
(5, 2, 'người sở hữu', '2023-05-15 08:00:00'), -- manager là chủ project 5
(5, 3, 'thành viên', '2023-05-16 09:00:00');

INSERT INTO Task (TaskTitle, TaskDescription, TaskStatusID, Priority, StartDate, EndDate, ProjectID, ParentTaskID) VALUES
('Thiết lập cấu trúc thư mục PHP', '', 1, 'Cao', '2023-01-12 08:00:00', '2023-01-20 17:00:00', 1, NULL),
('Thiết kế cơ sở dữ liệu', 'Thiết kế cấu trúc cơ sở dữ liệu cho hệ thống', 2, 'Cao', '2023-01-21 08:00:00', '2023-01-30 17:00:00', 1, 1),
('Phát triển giao diện người dùng', 'Xây dựng giao diện người dùng theo bản thiết kế', 2, 'Trung Bình', '2023-02-01 08:00:00', '2023-02-15 17:00:00', 1, 1),
('Viết giao diện Form đăng nhập', 'Thực hiện kiểm thử toàn bộ hệ thống', 1, 'Cao', '2023-02-20 08:00:00', '2023-03-01 17:00:00', 1, NULL),
('Thiết kế giao diện website', 'Thiết kế giao diện cho website bán hàng', 2, 'Trung Bình', '2023-02-16 08:00:00', '2023-02-28 17:00:00', 2, NULL),
('Xây dựng module thanh toán', 'Phát triển module thanh toán trực tuyến', 1, 'Khẩn cấp', '2023-03-01 08:00:00', '2023-03-15 17:00:00', 2, 5),
('Cấu hình môi trường PHP', 'Cài đặt PHP, Apache, MySQL', 1, 'Trung Bình', '2023-01-12 09:00:00', '2023-01-13 17:00:00', 1, 1),
('Tạo file cấu hình', 'Tạo file config cho project', 1, 'Thấp', '2023-01-13 08:00:00', '2023-01-14 17:00:00', 1, 1);

INSERT INTO TaskAssignment (TaskID, UserID, AssignedAt, AssignedBy) VALUES
(1, 3, '2023-01-12 09:00:00', 1),
(2, 3, '2023-01-21 09:00:00', 1),
(3, 4, '2023-02-01 09:00:00', 2),
(4, 5, '2023-02-20 09:00:00', 1),
(5, 4, '2023-02-16 09:00:00', 2),
(6, 3, '2023-03-01 09:00:00', 2);

INSERT INTO TaskStatusHistory (TaskID, OldStatusID, NewStatusID, ChangedBy, ChangedAt) VALUES
(2, 1, 2, 3, '2023-01-25 11:30:00'),
(3, 1, 2, 4, '2023-02-05 14:20:00'),
(5, 1, 2, 4, '2023-02-20 09:15:00'),
(1, 1, 3, 3, '2023-01-19 16:45:00');

-- Thêm Task mẫu ở trạng thái 'Đã làm' để có dữ liệu cho cột hoàn thành
INSERT INTO Task (TaskTitle, TaskDescription, TaskStatusID, Priority, StartDate, EndDate, ProjectID, ParentTaskID) VALUES
  ('Hoàn tất kiểm thử unit', 'Viết unit test cho các module chính', 3, 'Trung Bình', '2023-02-25 08:00:00', '2023-03-05 17:00:00', 1, NULL),
  ('Triển khai môi trường staging', 'Deploy code lên server staging', 3, 'Cao', '2023-03-06 08:00:00', '2023-03-07 17:00:00', 1, NULL),
  ('Kiểm thử tích hợp', 'Test end-to-end toàn bộ luồng nghiệp vụ', 3, 'Khẩn cấp', '2023-03-08 08:00:00', '2023-03-10 17:00:00', 1, NULL);

-- Nếu bạn muốn thêm cho các project khác, chỉ cần đổi ProjectID
INSERT INTO Task (TaskTitle, TaskDescription, TaskStatusID, Priority, StartDate, EndDate, ProjectID, ParentTaskID) VALUES
  ('Hoàn thiện thiết kế UI', 'Chốt giao diện cuối cùng', 3, 'Cao', '2023-02-20 08:00:00', '2023-02-22 17:00:00', 2, NULL),
  ('Kiểm thử chức năng thanh toán', 'Test mọi kịch bản thanh toán', 3, 'Trung Bình', '2023-03-16 08:00:00', '2023-03-20 17:00:00', 2, NULL);