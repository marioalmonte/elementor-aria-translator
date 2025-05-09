<?php
/**
 * Clase para la captura de atributos ARIA
 *
 * @package AccessiTrans
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

/**
 * Clase para la captura de atributos ARIA
 */
class AccessiTrans_Capture {
    
    /**
     * Referencia a la clase principal
     */
    private $core;
    
    /**
     * Cache para evitar procesar múltiples veces el mismo HTML
     */
    private $processed_html = [];
    
    /**
     * Constructor
     */
    public function __construct($core) {
        $this->core = $core;
        
        // Inicializar métodos de captura según las opciones configuradas
        $this->init_capture_methods();
    }
    
    /**
     * Inicializa los métodos de captura según la configuración
     */
    private function init_capture_methods() {
        // Verificar si el escaneo está permitido globalmente
        if (!isset($this->core->options['permitir_escaneo']) || !$this->core->options['permitir_escaneo']) {
            if ($this->core->options['modo_debug']) {
                $this->core->log_debug("Escaneo global desactivado - No se inicializarán métodos de captura");
            }
            return;
        }
        
        // Verificación especial para entorno de administración
        if (is_admin() && !wp_doing_ajax()) {
            $current_language = apply_filters('wpml_current_language', null);
            $default_language = apply_filters('wpml_default_language', null);
            
            if ($current_language !== $default_language) {
                if ($this->core->options['modo_debug']) {
                    $this->core->log_debug("Admin: No se inicializarán métodos de captura en idioma no predeterminado ({$current_language})");
                }
                return;
            }
        }
        
        // Verificación general de idioma
        if (!$this->core->should_capture_in_current_language()) {
            if ($this->core->options['modo_debug']) {
                $this->core->log_debug("No se inicializarán métodos de captura en idioma no predeterminado");
            }
            return;
        }
        
        // MÉTODO 1: Capturar el HTML completo si está habilitado
        if ($this->core->options['captura_total']) {
            add_action('wp_footer', [$this, 'capture_full_html'], 999);
        }
        
        // MÉTODO 2: Hook para procesar el contenido de Elementor
        if ($this->core->options['captura_elementor']) {
            add_filter('elementor/frontend/the_content', [$this, 'capture_aria_in_content'], 999);
        }
        
        // MÉTODO 3: Hooks de Elementor para capturar widgets y elementos
        if ($this->core->options['procesar_elementos']) {
            add_action('elementor/frontend/widget/before_render_content', [$this, 'process_element_attributes'], 10, 1);
            add_action('elementor/frontend/before_render', [$this, 'process_element_attributes'], 10, 1);
        }
        
        // MÉTODO 4: Hook para templates de Elementor
        if ($this->core->options['procesar_templates']) {
            add_action('elementor/frontend/builder_content_data', [$this, 'process_template_data'], 10, 2);
        }
    }
    
    /**
     * Captura el HTML completo de la página para buscar atributos ARIA
     */
    public function capture_full_html() {
        // Verificar si debemos procesar según la configuración
        if ($this->core->options['solo_admin'] && !current_user_can('manage_options')) {
            return;
        }
        
        // Verificar si debe capturar usando el método centralizado
        if (!$this->core->should_capture()) {
            return;
        }
        
        // Iniciar captura de salida
        ob_start();
        
        // Al finalizar la página, procesar el HTML completo
        add_action('shutdown', function() {
            $html_completo = ob_get_clean();
            if (!empty($html_completo)) {
                $this->extract_aria_from_html($html_completo);
                
                /**
                 * NOTA ESPECIAL DE SEGURIDAD:
                 * Esta es una situación donde necesitamos procesar y devolver el HTML completo
                 * de la página, manteniendo su estructura intacta para el correcto funcionamiento.
                 * Siguiendo las recomendaciones de WordPress para estos casos excepcionales,
                 * aplicamos escape específico y marcamos la variable como segura.
                 */
                $html_completo_safe = $html_completo;
                
                // IMPORTANTE: Usamos wp_kses_post para eliminar código malicioso 
                // pero preservar la estructura HTML válida
                echo wp_kses_post($html_completo_safe);
            }
        }, 0);
    }
    
    /**
     * Extrae y registra todos los atributos ARIA del HTML
     */
    public function extract_aria_from_html($html) {
        if (empty($html) || !is_string($html)) {
            return;
        }
        
        // Verificación adicional del idioma actual antes de procesar
        if (!$this->core->should_capture_in_current_language()) {
            if ($this->core->options['modo_debug']) {
                $current_language = apply_filters('wpml_current_language', null);
                $default_language = apply_filters('wpml_default_language', null);
                $this->core->log_debug("Omitiendo extracción de HTML - idioma actual ({$current_language}) != ({$default_language})");
            }
            return;
        }
        
        // Crear una huella única del HTML para evitar reprocesar el mismo contenido
        $html_hash = md5($html);
        if (isset($this->processed_html[$html_hash])) {
            return;
        }
        $this->processed_html[$html_hash] = true;
        
        if ($this->core->options['modo_debug']) {
            $this->core->log_debug("Procesando HTML para buscar atributos ARIA");
        }
        
        // Verificar si debemos capturar usando el método centralizado
        if (!$this->core->should_capture()) {
            return;
        }
        
        foreach ($this->core->get_traducible_attrs() as $attr) {
            // Patrón para buscar el atributo con cualquier tipo de comillas
            $pattern = '/' . preg_quote($attr, '/') . '\s*=\s*([\'"])((?:(?!\1).)*)\1/is';
            
            if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $attr_value = $match[2];
                    
                    // No procesar valores vacíos o puramente numéricos
                    if (empty($attr_value) || is_numeric($attr_value)) {
                        continue;
                    }
                    
                    // Normalizar el valor
                    $attr_value = $this->core->translator->prepare_string($attr_value);
                    
                    // Buscar el ID del elemento que contiene el atributo
                    $element_id = $this->extract_element_id_from_context($html, $match[0]);
                    
                    // Verificar que la cadena no es una traducción en WPML
                    if (!$this->core->translator->is_wpml_translation($attr_value)) {
                        // Registrar el valor para traducción
                        $this->core->translator->register_value_for_translation($attr, $attr_value, $element_id);
                        
                        if ($this->core->options['modo_debug']) {
                            $this->core->log_debug("CAPTURA - {$attr} = \"" . esc_html($attr_value) . "\"" . ($element_id ? " (ID: " . esc_html($element_id) . ")" : ""));
                        }
                    } else if ($this->core->options['modo_debug']) {
                        $this->core->log_debug("OMITIENDO - {$attr} = \"" . esc_html($attr_value) . "\" - Es una traducción existente en WPML");
                    }
                }
            }
        }
    }
    
    /**
     * Extrae el ID del elemento que contiene el atributo ARIA
     */
    private function extract_element_id_from_context($html, $attr_match) {
        // Buscar la posición del atributo en el HTML
        $pos = strpos($html, $attr_match);
        if ($pos === false) {
            return '';
        }
        
        // Extraer un fragmento de HTML antes del atributo
        $start = max(0, $pos - 200);
        $fragment = substr($html, $start, 400);
        
        // Buscar el atributo "id" o "data-id" en este fragmento
        $id_pattern = '/\s(?:id|data-id|data-element-id)=[\'"]([^\'"]+)[\'"]/i';
        if (preg_match($id_pattern, $fragment, $matches)) {
            return $matches[1];
        }
        
        return '';
    }
    
    /**
     * Procesa el contenido de Elementor para buscar atributos ARIA
     */
    public function capture_aria_in_content($content) {
        // Verificar si debe capturar usando el método centralizado
        if (!$this->core->should_capture()) {
            return $content;
        }
        
        // Verificación adicional del idioma actual antes de procesar
        if (!$this->core->should_capture_in_current_language()) {
            return $content;
        }
        
        if (!empty($content) && is_string($content)) {
            $this->extract_aria_from_html($content);
        }
        return $content;
    }
    
    /**
     * Procesa cualquier elemento de Elementor
     */
    public function process_element_attributes($element) {
        // Verificar si debe capturar usando el método centralizado
        if (!$this->core->should_capture()) {
            return;
        }
        
        // Verificación adicional del idioma actual antes de procesar
        if (!$this->core->should_capture_in_current_language()) {
            return;
        }
        
        if (!is_object($element)) {
            return;
        }
        
        try {
            // Intentar obtener settings
            $settings = null;
            if (method_exists($element, 'get_settings_for_display')) {
                $settings = $element->get_settings_for_display();
            } elseif (method_exists($element, 'get_settings')) {
                $settings = $element->get_settings();
            }
            
            if (!is_array($settings)) {
                return;
            }
            
            // Obtener ID del elemento
            $element_id = method_exists($element, 'get_id') ? $element->get_id() : '';
            if (empty($element_id)) {
                return;
            }
            
            // Procesar custom_attributes si existen
            if (isset($settings['custom_attributes'])) {
                $this->process_custom_attributes($settings['custom_attributes'], $element_id);
            }
            
            // También buscar en otras propiedades del elemento
            foreach ($settings as $key => $value) {
                // Buscar propiedades que podrían contener atributos ARIA
                if (is_string($key) && (
                    strpos($key, 'aria_') === 0 || 
                    strpos($key, 'aria-') === 0 || 
                    strpos($key, 'role') === 0 ||
                    strpos($key, 'accessibility') !== false
                )) {
                    // Si es un valor string, registrarlo para traducción
                    if (is_string($value) && !empty($value)) {
                        // Determinar el nombre del atributo
                        $attr_name = str_replace('_', '-', $key);
                        if (strpos($attr_name, 'aria-') !== 0) {
                            $attr_name = 'aria-label'; // Default
                        }
                        
                        // Normalizar el valor
                        $value = $this->core->translator->prepare_string($value);
                        
                        // Verificar que no sea una traducción
                        if (!$this->core->translator->is_wpml_translation($value)) {
                            // Registrar el valor para traducción
                            $this->core->translator->register_value_for_translation($attr_name, $value, $element_id);
                            
                            if ($this->core->options['modo_debug']) {
                                $this->core->log_debug("Encontrado en settings: " . esc_html($attr_name) . " = \"" . esc_html($value) . "\" (ID: " . esc_html($element_id) . ")");
                            }
                        }
                    }
                }
            }
            
        } catch (\Exception $e) {
            if ($this->core->options['modo_debug']) {
                $this->core->log_debug("Error en process_element_attributes: " . esc_html($e->getMessage()));
            }
        }
    }
    
    /**
     * Procesa datos de template de Elementor
     */
    public function process_template_data($data, $post_id) {
        // Verificar si debe capturar usando el método centralizado
        if (!$this->core->should_capture()) {
            return $data;
        }
        
        // Verificación adicional del idioma actual antes de procesar
        if (!$this->core->should_capture_in_current_language()) {
            return $data;
        }
        
        if (empty($data) || !is_array($data)) {
            return $data;
        }
        
        if ($this->core->options['modo_debug']) {
            $this->core->log_debug("Procesando template data para post ID: " . esc_html($post_id));
        }
        
        $this->process_template_elements($data);
        
        return $data;
    }
    
    /**
     * Procesa recursivamente elementos de template
     */
    private function process_template_elements($elements) {
        if (!is_array($elements)) {
            return;
        }
        
        // Verificación adicional del idioma actual antes de procesar
        if (!$this->core->should_capture_in_current_language()) {
            return;
        }
        
        foreach ($elements as $element) {
            if (!is_array($element)) {
                continue;
            }
            
            // Procesar settings si existen
            if (isset($element['settings']) && is_array($element['settings']) && 
                isset($element['settings']['custom_attributes'])) {
                
                $element_id = isset($element['id']) ? $element['id'] : '';
                if (!empty($element_id)) {
                    $this->process_custom_attributes($element['settings']['custom_attributes'], $element_id);
                }
            }
            
            // Procesar elementos hijos
            if (isset($element['elements']) && is_array($element['elements'])) {
                $this->process_template_elements($element['elements']);
            }
        }
    }
    
    /**
     * Procesa atributos personalizados de Elementor
     */
    private function process_custom_attributes($custom_attributes, $element_id) {
        if (empty($custom_attributes)) {
            return;
        }
        
        // Verificación adicional del idioma actual antes de procesar
        if (!$this->core->should_capture_in_current_language()) {
            return;
        }
        
        // CASO 1: Array de objetos (formato normal)
        if (is_array($custom_attributes)) {
            foreach ($custom_attributes as $attribute) {
                if (is_array($attribute) && isset($attribute['key']) && isset($attribute['value'])) {
                    $key = $attribute['key'];
                    $value = $attribute['value'];
                    
                    if (in_array($key, $this->core->get_traducible_attrs())) {
                        // Normalizar el valor
                        $value = $this->core->translator->prepare_string($value);
                        
                        // Verificar que no sea una traducción
                        if (!$this->core->translator->is_wpml_translation($value)) {
                            $this->core->translator->register_value_for_translation($key, $value, $element_id);
                            
                            if ($this->core->options['modo_debug']) {
                                $this->core->log_debug("Encontrado atributo: " . esc_html($key) . " = \"" . esc_html($value) . "\" (ID: " . esc_html($element_id) . ")");
                            }
                        }
                    }
                }
                // Formato key|value como string
                elseif (is_string($attribute) && strpos($attribute, '|') !== false) {
                    list($key, $value) = explode('|', $attribute, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    if (in_array($key, $this->core->get_traducible_attrs())) {
                        // Normalizar el valor
                        $value = $this->core->translator->prepare_string($value);
                        
                        // Verificar que no sea una traducción
                        if (!$this->core->translator->is_wpml_translation($value)) {
                            $this->core->translator->register_value_for_translation($key, $value, $element_id);
                            
                            if ($this->core->options['modo_debug']) {
                                $this->core->log_debug("Encontrado atributo pipe: " . esc_html($key) . " = \"" . esc_html($value) . "\" (ID: " . esc_html($element_id) . ")");
                            }
                        }
                    }
                }
            }
        }
        // CASO 2: String multilínea
        elseif (is_string($custom_attributes)) {
            $lines = preg_split('/\r\n|\r|\n/', $custom_attributes);
            
            foreach ($lines as $line) {
                if (strpos($line, '|') !== false) {
                    list($key, $value) = explode('|', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    if (in_array($key, $this->core->get_traducible_attrs())) {
                        // Normalizar el valor
                        $value = $this->core->translator->prepare_string($value);
                        
                        // Verificar que no sea una traducción
                        if (!$this->core->translator->is_wpml_translation($value)) {
                            $this->core->translator->register_value_for_translation($key, $value, $element_id);
                            
                            if ($this->core->options['modo_debug']) {
                                $this->core->log_debug("Encontrado atributo multilínea: " . esc_html($key) . " = \"" . esc_html($value) . "\" (ID: " . esc_html($element_id) . ")");
                            }
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Limpia la caché después de guardar en Elementor
     */
    public function clear_cache() {
        $this->processed_html = [];
    }
}