-- SQL Script to insert sample plagiarism sources for testing

-- Academic sources
INSERT INTO `plagiarism_sources` 
(`source_text`, `source_name`, `source_url`, `category`) 
VALUES 
('The impact of climate change on global ecosystems remains one of the most pressing environmental challenges of the 21st century. Rising temperatures, shifting precipitation patterns, and increasingly frequent extreme weather events are altering habitats worldwide.', 
'Climate Change Review', 
'https://example.com/climate-research', 
'Academic');

INSERT INTO `plagiarism_sources` 
(`source_text`, `source_name`, `source_url`, `category`) 
VALUES 
('Artificial intelligence has fundamentally transformed numerous industries, from healthcare to finance. Machine learning algorithms can now diagnose diseases, predict market trends, and automate complex tasks with remarkable accuracy.', 
'AI Technology Journal', 
'https://example.com/ai-journal', 
'Academic');

INSERT INTO `plagiarism_sources` 
(`source_text`, `source_name`, `source_url`, `category`) 
VALUES 
('Quantum computing represents a paradigm shift in computational capabilities. Unlike classical computers that use bits, quantum computers utilize quantum bits or qubits, which can exist in multiple states simultaneously due to the principle of superposition.', 
'Quantum Computing Research', 
'https://example.com/quantum-research', 
'Academic');

-- Literary sources
INSERT INTO `plagiarism_sources` 
(`source_text`, `source_name`, `source_url`, `category`) 
VALUES 
('It was the best of times, it was the worst of times, it was the age of wisdom, it was the age of foolishness, it was the epoch of belief, it was the epoch of incredulity, it was the season of Light, it was the season of Darkness.', 
'A Tale of Two Cities', 
'https://example.com/dickens-works', 
'Literary');

INSERT INTO `plagiarism_sources` 
(`source_text`, `source_name`, `source_url`, `category`) 
VALUES 
('In my younger and more vulnerable years my father gave me some advice that I've been turning over in my mind ever since. "Whenever you feel like criticizing anyone," he told me, "just remember that all the people in this world haven't had the advantages that you've had."', 
'The Great Gatsby', 
'https://example.com/fitzgerald-works', 
'Literary');

-- Web content
INSERT INTO `plagiarism_sources` 
(`source_text`, `source_name`, `source_url`, `category`) 
VALUES 
('Effective time management is crucial for productivity in today's fast-paced world. Prioritizing tasks, eliminating distractions, and setting clear goals can significantly improve workflow efficiency and reduce stress levels.', 
'Productivity Tips Blog', 
'https://example.com/productivity-blog', 
'Web');

INSERT INTO `plagiarism_sources` 
(`source_text`, `source_name`, `source_url`, `category`) 
VALUES 
('A balanced diet should include a variety of fruits, vegetables, whole grains, lean proteins, and healthy fats. Proper nutrition is essential for maintaining energy levels, supporting immune function, and preventing chronic diseases.', 
'Nutrition Guide', 
'https://example.com/nutrition', 
'Web');

-- Technical documentation
INSERT INTO `plagiarism_sources` 
(`source_text`, `source_name`, `source_url`, `category`) 
VALUES 
('JavaScript is a high-level, interpreted programming language that conforms to the ECMAScript specification. It has curly-bracket syntax, dynamic typing, prototype-based object-orientation, and first-class functions.', 
'JavaScript Documentation', 
'https://example.com/javascript-docs', 
'Technical');

INSERT INTO `plagiarism_sources` 
(`source_text`, `source_name`, `source_url`, `category`) 
VALUES 
('Containerization technology allows developers to package applications along with their dependencies, ensuring consistent behavior across different environments. Docker has emerged as the leading platform for container management.', 
'DevOps Handbook', 
'https://example.com/devops-guide', 
'Technical');

-- Business documents
INSERT INTO `plagiarism_sources` 
(`source_text`, `source_name`, `source_url`, `category`) 
VALUES 
('Market segmentation is the process of dividing a broad consumer or business market into sub-groups based on shared characteristics. Effective segmentation allows companies to target their products and marketing efforts more precisely.', 
'Marketing Fundamentals', 
'https://example.com/marketing-guide', 
'Business');

INSERT INTO `plagiarism_sources` 
(`source_text`, `source_name`, `source_url`, `category`) 
VALUES 
('Strategic planning involves defining an organization's direction and making decisions on allocating its resources to pursue this strategy. It is the formal consideration of an organization's future course.', 
'Business Strategy Journal', 
'https://example.com/strategy-journal', 
'Business');