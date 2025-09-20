-- Add missing fields to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS middle_name VARCHAR(100),
ADD COLUMN IF NOT EXISTS date_of_birth DATE,
ADD COLUMN IF NOT EXISTS gender ENUM('male', 'female', 'other'),
ADD COLUMN IF NOT EXISTS marital_status ENUM('single', 'married', 'divorced', 'widowed'),
ADD COLUMN IF NOT EXISTS nationality VARCHAR(50),
ADD COLUMN IF NOT EXISTS residential_address TEXT,
ADD COLUMN IF NOT EXISTS postal_address TEXT,
ADD COLUMN IF NOT EXISTS city VARCHAR(100),
ADD COLUMN IF NOT EXISTS region VARCHAR(50),
ADD COLUMN IF NOT EXISTS postal_code VARCHAR(20),
ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255);

-- Add next of kin fields to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS next_of_kin_name VARCHAR(200),
ADD COLUMN IF NOT EXISTS next_of_kin_relationship VARCHAR(50),
ADD COLUMN IF NOT EXISTS next_of_kin_phone VARCHAR(20),
ADD COLUMN IF NOT EXISTS next_of_kin_email VARCHAR(100),
ADD COLUMN IF NOT EXISTS next_of_kin_dob DATE,
ADD COLUMN IF NOT EXISTS next_of_kin_occupation VARCHAR(100),
ADD COLUMN IF NOT EXISTS next_of_kin_address TEXT;

-- Add employment fields to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS occupation VARCHAR(100),
ADD COLUMN IF NOT EXISTS employment_status ENUM('employed', 'self_employed', 'business_owner', 'unemployed', 'student', 'retired'),
ADD COLUMN IF NOT EXISTS employer_name VARCHAR(200),
ADD COLUMN IF NOT EXISTS monthly_income DECIMAL(15,2),
ADD COLUMN IF NOT EXISTS work_address TEXT,
ADD COLUMN IF NOT EXISTS work_phone VARCHAR(20);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_phone ON users(phone);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_region ON users(region);
CREATE INDEX IF NOT EXISTS idx_users_city ON users(city);

SELECT 'Users table updated successfully!' as status;
