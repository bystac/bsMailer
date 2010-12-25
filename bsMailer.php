<?php

//require_once 'config.php';

error_reporting(E_ALL | E_STRICT);
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);

// path to zend framework if not in include path already
set_include_path('../../zf/library' . PATH_SEPARATOR . get_include_path());

require_once 'Zend/Mail.php';

class bsMailer
{
	protected $path_templates = null;
	protected $template_name = null;
	protected $template_full_path = null;
	protected $variables = null;
	protected $language = 'en';
	protected $email_from = null;
	protected $email_to = null;

	protected $zfMail = null;

	private $mail_content_text = null;
	private $mail_content_html = null;
	private $mail_content_subject = null;

	public function __construct($path='', $tpl='', $lang='', $charset='utf-8') {
		if (!empty($path)) {
			$this->setPathTemplates($path);
		}

		if (!empty($lang)) {
			$this->setLanguage($lang);
		}

		if (!empty($tpl)) {
			$this->setTemplate($tpl);
		}

		$this->zfMail = new Zend_Mail($charset);
	}

	/**
	 * setPathTemplates
	 * path to templates which will be used to send emails
	 *
	 * @param string $path
	 * @return boolean
	 */
	public function setPathTemplates($path) {
		if (is_dir($path)) {
			$this->path_templates = rtrim($path, '/').'/';
			return true;
		}
		return false;
	}

	public function setTemplate($template) {
		if (empty($template)) {
			$this->template = null;
			return false;
		}

		if ($this->path_templates!==null && !is_dir($this->path_templates . $template) ) {
			$this->template = null;
			return false;
		}
		
		$this->template = ltrim(rtrim($template, '/'), '/').'/';
		return true;
	}

	/**
	 * setLanguage
	 * language of the template to fetch
	 * @todo check if language code is correct
	 * 
	 * @param string $lang iso2 language code
	 */
	public function setLanguage($lang) {
		if (strlen($lang) != 2) {
			$this->language = 'en';
			return false;
		}

		$this->language = $lang;
		return true;
	}

	public function getTemplatePath($type = 'text') {
		if ( !in_array($type, array('text', 'html', 'subject')) ) {
			return false;
		}
		
		return $this->path_templates . $this->template_name . $this->language . '.' . $type;
	}

	public function setVariables($vars) {
		if (count($vars)) {
			$this->variables = $vars;
			return true;
		}
		return false;
	}

	public function setEmailTo($email, $name='', $append=false) {
		if ($append === false) {
			$this->zfMail->clearRecipients();
		}

		$this->zfMail->addTo($email, $name);
	}
	public function setEmailFrom($email, $name='') {
		$this->zfMail->setFrom($email, $name);
	}

	public function getTemplateContent($type='subject', $embedVars = false) {

		$path = $this->getTemplatePath($type);
		if ($path===false)
			return false;

		$content = file_get_contents($path);

		if ($embedVars) {
			$var_keys = $this->embraceArrayElement(array_keys($this->variables));
			$var_vals = array_values($this->variables);
			$content = str_ireplace($var_keys, $var_vals, $content);
		}

		return $content;

	}
	private function loadTemplateContent($reload = true) {
		if ($reload || $this->mail_content_text === null) {
			$path_text = $this->getTemplatePath('text');
			if ($path_text !== false && file_exists($path_text)) {
				$this->mail_content_text = file_get_contents($path_text);
			}
		}

		if ($reload || $this->mail_content_text === null) {
			$path_html = $this->getTemplatePath('html');
			if ($path_html !== false && file_exists($path_html)) {
				$this->mail_content_html = file_get_contents($path_html);
			}
		}

		if ($reload || $this->mail_content_subject === null) {
			$path_subject = $this->getTemplatePath('subject');
			if ($path_subject !== false && file_exists($path_subject)) {
				$this->mail_content_subject = file_get_contents($path_subject);
			}
		}
	}

	private function embedTemplateVars($reload = true) {

		$this->loadTemplateContent(true);
	
		$var_keys = $this->embraceArrayElement(array_keys($this->variables));
		$var_vals = array_values($this->variables);
		$is_subject_exists = false;
		$is_content_exists = false;

		if ($this->mail_content_text != null) {
			$tmp_text = str_ireplace($var_keys, $var_vals, $this->mail_content_text);
			$this->zfMail->setBodyText($tmp_text);
			$is_content_exists=true;
		}

		if ($this->mail_content_html != null) {
			$tmp_html = str_ireplace($var_keys, $var_vals, $this->mail_content_html);
			$this->zfMail->setBodyHtml($tmp_html);
			$is_content_exists=true;
		}

		if ($this->mail_content_subject != null) {
			$tmp_subject = str_ireplace($var_keys, $var_vals, $this->mail_content_subject);
			$this->zfMail->setSubject($tmp_subject);
			$is_subject_exists=true;
		}

		return $is_content_exists && $is_subject_exists;
	}

	private function embraceArrayElement($arr) {
		$embraced = array();
		if (count($arr)) {
			foreach($arr as $a) {
				$embraced[] = '{'.$a.'}';
			}
		}
		return $embraced;
	}
	public function send($reload_template_vars = false) {
		if ($this->embedTemplateVars($reload_template_vars)) {
			$this->zfMail->send();
			return true;
		}

		return false;
	}

}
