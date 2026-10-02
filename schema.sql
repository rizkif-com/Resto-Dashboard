CREATE TABLE positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    base_salary DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    hourly_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    permission_level INT NOT NULL DEFAULT 1,
    weekly_target_hours INT NOT NULL DEFAULT 40,
    show_weekly_target BOOLEAN NOT NULL DEFAULT TRUE,
    can_cook BOOLEAN NOT NULL DEFAULT FALSE,
    can_promote BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    discord_id VARCHAR(255) NOT NULL UNIQUE,
    username VARCHAR(255) NOT NULL,
    position_id INT,
    sp_level INT NOT NULL DEFAULT 0,
    FOREIGN KEY (position_id) REFERENCES positions(id)
);

CREATE TABLE duty_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    clock_in DATETIME NOT NULL,
    clock_out DATETIME,
    is_manual BOOLEAN DEFAULT FALSE,
    status ENUM('PENDING', 'PROCESSED') DEFAULT 'PENDING',
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

CREATE TABLE ingredients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    image_url VARCHAR(255) NULL
);

CREATE TABLE recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    cooking_bonus DECIMAL(10, 2) NOT NULL DEFAULT 0.00
);

CREATE TABLE recipe_ingredients (
    recipe_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    quantity_required INT NOT NULL DEFAULT 1,
    PRIMARY KEY (recipe_id, ingredient_id),
    FOREIGN KEY (recipe_id) REFERENCES recipes(id),
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id)
);

CREATE TABLE cook_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    recipe_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    photo_proof_path VARCHAR(255) NOT NULL,
    status ENUM('PENDING', 'PROCESSED') DEFAULT 'PENDING',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id),
    FOREIGN KEY (recipe_id) REFERENCES recipes(id)
);

CREATE TABLE donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donor_name VARCHAR(255) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE staff_sp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    reason TEXT NOT NULL,
    issued_by INT NOT NULL,
    issued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id),
    FOREIGN KEY (issued_by) REFERENCES staff(id)
);

CREATE TABLE payroll_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    week_start DATE NOT NULL,
    week_end DATE NOT NULL,
    base_salary DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    overtime_pay DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    bonus_pay DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total_pay DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    status ENUM('UNPAID', 'PAID') DEFAULT 'UNPAID',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    type ENUM('SP', 'SALARY_PAID', 'OTHER') DEFAULT 'OTHER',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    reference_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);
