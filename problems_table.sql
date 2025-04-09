CREATE TABLE problems (
    id INT NOT NULL AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    language VARCHAR(50) NOT NULL,
    code TEXT NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard') NOT NULL,
    tags VARCHAR(255),
    solution TEXT NOT NULL,
    points INT NOT NULL,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
