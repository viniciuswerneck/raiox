-- Limpa os relatórios de locais cacheados (pesquisas)
TRUNCATE TABLE `location_reports`;

-- Limpa os bairros salvos
TRUNCATE TABLE `neighborhoods`;

-- Limpa as cidades (isso quebra dependências se neighborhoods tentar apontar para a cidade pai, 
-- por isso executamos na ordem certa para evitar travamentos de chaves estrangeiras)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `cities`;
TRUNCATE TABLE `neighborhoods`;
TRUNCATE TABLE `location_reports`;
SET FOREIGN_KEY_CHECKS = 1;
