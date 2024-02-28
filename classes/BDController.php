<?php
class BDController{
	private $user_id;
	private $user_name;
	private $user_email;
	private $course_id;
	private $filepath;
	private $transcript_filepath;
	private $db_filepath;
	private $transcript_db_filepath;
	private $error_messages = array();
    private $certificate_id;
	private $serial_number;
	private $pdf;
	private $transcript_pdf;
	private $curriculums;
	private $table_name;
	private $common_path;
	private $title;
	private $certificate_clicked;
	private $transcript_clicked;
	private $certificate_status = false;
	private $transcript_status = false;
	public function __construct(){
		$this->user_id = get_current_user_id();
		$user_data = get_userdata($this->user_id);
//		$this->user_name = $user_data->display_name;
		$this->user_email = $user_data->user_email;
		$this->table_name = 'wp_sa_certificate_automation';
//		$this->common_path = get_stylesheet_directory_uri().'/assets/images/certificate-transcript';
		$this->common_path = WP_PLUGIN_DIR . '/ct-automation/assets/images';
	}
	public function UpdateUserCT($course_id, $name, $certificate_clicked, $transcript_clicked): array {
		$this->user_name = $name !== '' ? $name : $this->user_name;
		$this->course_id = $course_id;
		$this->certificate_clicked = $certificate_clicked;
		$this->transcript_clicked = $transcript_clicked;
		$this->GenerateCertificatePdf();
		global $wpdb;
		$wpdb->query('START TRANSACTION');
		try {
			if (!$this->ValidateCourse()) {
				$this->error_messages[] = 'Failed!';
				$mail_status = $this->SendAdminNotification();
				if(!$mail_status){
					$this->error_messages[] = 'Failed to send email to admin!';
				}else{
					$this->error_messages[] = 'Error report Send successfully to the admin.';
				}
				return [
					'success' => false,
					'errors' => $this->error_messages,
					'mail_status' => $mail_status,
				];
			}
			// short circuit operation
			$certOperationsSuccess = $this->certificate_clicked && $this->UpdateCertificateMeta() && $this->GenerateCertificatePdf() && $this->AddCertificateAutomation();
			$tranOperationsSuccess = $this->transcript_clicked && $this->CheckTranscriptMeta() && $this->GenerateTranscriptPdf() && $this->AddTranscriptAutomation();

			if($certificate_clicked){
				if($certOperationsSuccess){
					$this->certificate_status = true;
					$this->pdf->Output('F', $this->filepath);
				}else{
					return $this->buildResponse(false);
				}
			}if($transcript_clicked){
				if($tranOperationsSuccess){
					$this->transcript_status = true;
					$this->transcript_pdf->Output('F', $this->transcript_filepath);
				}else{
					return $this->buildResponse(false);
				}
			}
//			elseif($certificate_clicked && $transcript_clicked){
//				if($certOperationsSuccess && $tranOperationsSuccess){
//					$this->pdf->Output('F', $this->filepath);
//					$this->transcript_pdf->Output('F', $this->transcript_filepath);
//					$this->certificate_status = true;
//					$this->transcript_status = true;
//				}else{
//					$this->certificate_status = false;
//					$this->transcript_status = false;
//					return $this->buildResponse(false,false, false);
//				}
//			}
			$wpdb->query('COMMIT');
			$mail_status = $this->SendUserNotification();
			if(!$mail_status){
				$this->error_messages[] = 'Failed to send email to the user!';
			}else{
				$this->error_messages[] = 'Mail Send successfully to the user.';
			}
//			return $this->buildResponse(true);

			return [
				'success' => true,
				'mail_status' => $mail_status,
				'certificate' => $this->certificate_status,
				'transcript' =>  $this->transcript_status,
			];
		} catch (Exception $e) {
			$wpdb->query('ROLLBACK');
			return [
				'success' => false,
				'errors' => $this->error_messages
			];
		}
	}
	public function ValidateCourse(): bool {
		if(empty($this->certificate_clicked) && empty($this->transcript_clicked)){
			$this->error_messages[] = "Certificate or Transcript not selected";
			return false;
		}
		if(empty($this->course_id)){
			$this->error_messages[] = "Please Select a Course";
			return false;
		}
		$course = get_post($this->course_id);
		if (!$course || is_wp_error( $course )) {
			$this->error_messages[] = "Course Not Found";
			return false;
		}
		$status = bp_course_get_user_course_status($this->user_id, $this->course_id);
		if ($status != 4) {
			$this->error_messages[] = "Please Complete the course first!";
			return false;
		}
		$meta_key = 'vibe_certificate_template';
		$certificate_template_id = get_post_meta($this->course_id, $meta_key, true);
		if(!is_numeric($certificate_template_id)){
			$this->error_messages[] = "Certificate template not assigned!";
			return false;
		}
		$title = get_the_title($this->course_id);
		$title = iconv('UTF-8', 'windows-1252', html_entity_decode($title));
		$this->title = $title;

		$this->certificate_id = $certificate_template_id;
		return true;
	}
	public function UpdateCertificateMeta(): bool {
		$meta_key = 'certificates';
		$certificates = get_user_meta($this->user_id, $meta_key, true) ?: array();
		if (!in_array($this->course_id, $certificates)) {
			$certificates[] = $this->course_id;
			$update_result = update_user_meta($this->user_id, $meta_key, $certificates);
			if ($update_result === false) {
				$this->error_messages[] = "Failed to update user meta.";
				return false;
			}
			return true;
		}
		return true;
//		$this->error_messages[] = "You have already purchased the certificate!";
//		return false;
	}
	public function CheckTranscriptMeta(): bool {
		return true;
//		global $wpdb;
//		$pdf_type = 'transcript';
//		$table_name = 'wp_sa_certificate_automation';
//		$query = $wpdb->prepare(
//			"SELECT * FROM $table_name WHERE user_id = %d AND course_id = %d AND pdf_type = %s",
//			$this->user_id,
//			$this->course_id,
//			$pdf_type
//		);
//		$results = $wpdb->get_results($query, ARRAY_A);
//		if(count($results)){
//			$this->error_messages[] = "You have already purchased the transcript!";
//			return false;
//		}
//		return true;
	}
	public function GenerateCertificatePdf(): bool {
		$_pdf = new FPDF('P', 'mm', 'A4');
		$_pdf->AddFont('Manrope-Medium','','Manrope-Medium.php');
		$_pdf->AddFont('Manrope-Bold','','Manrope-Bold.php');
		$_pdf->SetTextColor(0, 35, 51);
		$this->pdf = $_pdf;
		$completion_date = $this->GetCourseCompletionDate();
		// Get image dimensions
		list($originalWidth, $originalHeight) = getimagesize($this->common_path.'/c1.jpg');

		// Calculate scaling factor based on page width and image aspect ratio
		$pageWidth = $_pdf->GetPageWidth();
		$scaleFactor = $pageWidth / $originalWidth;

		// Calculate new image width and height based on scaling factor
		$newWidth = $originalWidth * $scaleFactor;
		$newHeight = $originalHeight * $scaleFactor;

		// Center the image horizontally
		$xPosition = ($_pdf->GetPageWidth() - $newWidth) / 2;
		$yPosition = ($_pdf->GetPageHeight() - $newHeight) / 2;

		// Add the image to the PDF
		$_pdf->AddPage();
		$_pdf->Image( $this->common_path.'/c1.jpg', $xPosition, $yPosition, $newWidth, $newHeight);

		$textWidth = 145;
		$centeredX = $xPosition + ($pageWidth - $textWidth) / 2;

		// Set the name
		$n_length = strlen($this->user_name);
		$n_size = $n_length > 20 ? 30 : 36;
		$_pdf->SetFont('Manrope-Bold', '', $n_size);
		$_pdf->SetXY($centeredX, $yPosition + 1.45 * $newHeight / 4);
		$_pdf->MultiCell($textWidth, 30, $this->user_name, 0, 'C', 0);

		// set the title
//		$this->title = "Minute Writing - Role and Respons Minute Writing a";
		$length = strlen($this->title);
		$font_size = $length > 33 ? 16 : 24;

		$position = $length <= 50 ? 1.95 : 1.89;
		$_pdf->SetFont('Manrope-Bold', '', $font_size);
		$_pdf->SetXY($centeredX, $yPosition + $position * $newHeight / 4);
		$_pdf->MultiCell($textWidth, 8, $this->title, 0, 'C', 0);
//		$_pdf->MultiCell($textWidth, 8, $length, 0, 'C', 0);

		// set date
		$_pdf->SetFont('Manrope-Medium', '', 12);
		$_pdf->SetXY($centeredX, $yPosition + 2.24 * $newHeight / 4);
		$_pdf->MultiCell($textWidth, 30, $completion_date, 0, 'C', 0);

		// set serial number
		$_pdf->SetFont('Manrope-Medium', '', 12);
		$_pdf->SetXY($centeredX, $yPosition + 2.44 * $newHeight / 4);
		$serial_number = $this->certificate_id."-".$this->course_id."-".$this->user_id;
		$this->serial_number = $serial_number;
		$_pdf->MultiCell($textWidth, 30, $serial_number, 0, 'C', 0);

		$pdf_filename = 'certificate_' . $this->user_id . '_' . $this->course_id . '_' . uniqid( '', true ) . '.pdf';
		$pdf_filepath = WP_CONTENT_DIR . '/uploads/sa-certificate-pdf/' . $pdf_filename;
		$pdf_db = wp_upload_dir()['baseurl'] . '/sa-certificate-pdf/' . $pdf_filename;
		$this->filepath = $pdf_filepath;
		$this->db_filepath = $pdf_db;

		return true;
	}
	public function AddCertificateAutomation(): bool {
		// Insert data into wp_sa_certificate_automation table
		global $wpdb;
		$data = array(
			'user_name' => $this->user_name,
			'user_id' => $this->user_id,
			'course_id' => $this->course_id,
			'cert_id' =>  $this->serial_number,
			'pdf_path' => $this->db_filepath,
			'pdf_type' => 'certificate',
			'assigned_date' => current_time('mysql')
		);
		$result = $wpdb->insert($this->table_name, $data);

		if ( ! $result ) {
			$this->error_messages[] = $wpdb->last_error;
			return false;
		}
		return true;
	}
	public function GetCourseCompletionDate(): bool|string {
		global $wpdb;
		$query = $wpdb->prepare("
		    SELECT activity.date_recorded
		    FROM {$wpdb->prefix}bp_activity AS activity
		    WHERE
		        activity.component = 'course'
		        AND activity.type = 'unit_complete'
		        AND activity.user_id = %d
		        AND activity.item_id = %d
		    ORDER BY date_recorded DESC
		    LIMIT 1",
			$this->user_id,
			$this->course_id
		);
		$date = $wpdb->get_var($query);
		if($date){
//			return date('j F Y', strtotime($date));
			return date('d/m/Y', strtotime($date));
		}
		return false;
	}
	public function GenerateTranscriptPdf(): bool {
		$transcript_pdf = new FPDF('P', 'mm', 'A4');
		$transcript_pdf->AddFont('Poppins-LightItalic','','Poppins-LightItalic.php');
		$transcript_pdf->AddFont('Poppins-Regular','','Poppins-Regular.php');

		$this->transcript_pdf = $transcript_pdf;

		$completion_date = $this->GetCourseCompletionDate();

		$get_curriculum = $this->GetCurriculum();
		if(!$get_curriculum){
			return false;
		}
		$chunkSize = 19;
		$curriculumCount = count($this->curriculums);
		$chunkSize = max(1, $chunkSize);
		$curriculums_chunk_array = array();
		$curriculums_chunk_array[] = array_slice($this->curriculums, 0, min($curriculumCount, 12));

		if($curriculumCount > 12){
			for ($startIndex = 12; $startIndex < $curriculumCount; $startIndex += $chunkSize) {
				$curriculums_chunk_array[] = array_slice($this->curriculums, $startIndex, $chunkSize);
			}
		}
		$start = 1;
		$chunks_length = count($curriculums_chunk_array);

		foreach ($curriculums_chunk_array as $chunk_key => $chunk){
			$isLast = ($chunk_key == $chunks_length - 1);
			if($chunks_length == 1){
				$default_column_height = 6;
				$image_url = $this->common_path.'/t4.jpg';
			} else if($start == 1){
				$default_column_height = 8;
				$image_url = $this->common_path.'/t1.jpg';
			} else if ($chunk_key == $isLast) {
				$default_column_height = 6;
				$image_url = $this->common_path.'/t3.jpg';
			}else{
				$image_url = $this->common_path.'/t2.jpg';
				$default_column_height = 8;
			}
			// Get image dimensions
			list($originalWidth, $originalHeight) = getimagesize($image_url);

			// Calculate scaling factor based on page width and image aspect ratio
			$pageWidth = $transcript_pdf->GetPageWidth();
			$scaleFactor = $pageWidth / $originalWidth;

			// Calculate new image width and height based on scaling factor
			$newWidth = $originalWidth * $scaleFactor;
			$newHeight = $originalHeight * $scaleFactor;

			// Center the image horizontally
			$xPosition = ($transcript_pdf->GetPageWidth() - $newWidth) / 2;
			$yPosition = ($transcript_pdf->GetPageHeight() - $newHeight) / 2;

			// Add the image to the PDF
			$transcript_pdf->AddPage();
			$transcript_pdf->Image($image_url, $xPosition, $yPosition, $newWidth, $newHeight);

			$textWidth = 145;
			$centeredX = $xPosition + ($pageWidth - $textWidth) / 2;
			if($start == 1){
				// Set the name
				$transcript_pdf->SetFont('Poppins-LightItalic', '', 10);
				$transcript_pdf->SetTextColor(67, 68, 68);
				$transcript_pdf->SetXY(63, $yPosition + 0.726 * $newHeight / 4);
				$transcript_pdf->MultiCell($textWidth, 30, $this->user_name, 0, 'L', 0);

				// set the title
				$length = strlen($this->title);
				$transcript_pdf->SetFont('Poppins-LightItalic', '', 10);
				$transcript_pdf->SetXY(63, $yPosition + 0.96 * $newHeight / 4);
				$transcript_pdf->MultiCell($textWidth, 8, $this->title, 0, 'L', 0);

				// set date
				$transcript_pdf->SetFont('Poppins-LightItalic', '', 10);
				$transcript_pdf->SetXY(63, $yPosition + 0.90 * $newHeight / 4);
				$transcript_pdf->MultiCell($textWidth, 30, $completion_date, 0, 'L', 0);
			}
			$transcript_pdf->SetFont('Poppins-Regular', '', 10);
			$transcript_pdf->SetTextColor(5, 35, 50);
			// set table
			$tableWidth = 137;
			$tableX = ($pageWidth - $tableWidth) / 2;
			$tableY = $start === 0 ? $yPosition + 0.96 * $newHeight / 4 : $yPosition + 1.35 * $newHeight / 4;
			$transcript_pdf->SetXY($tableX, $tableY);

			$leftColumnWidth = $tableWidth * 0.2;
			$rightColumnWidth = $tableWidth * 0.8;

			$transcript_pdf->SetDrawColor(0, 89, 107);

			foreach($chunk as $key => $value) {
				$value = iconv('UTF-8', 'windows-1252', html_entity_decode($value));
				$transcript_pdf->SetX($tableX);
				$module_length = strlen($value);

				$second_column = $module_length > 58 ? 5 : $default_column_height;
				$firstColumn = $second_column < 6 ? 10 : $default_column_height;
				$transcript_pdf->Cell($leftColumnWidth, $firstColumn, $key, 'TL', 0, 'C');
				$transcript_pdf->MultiCell($rightColumnWidth, $second_column, $value, 'TLR', 'L');
//				$borderWidth = 0.01;
//				$transcript_pdf->SetLineWidth($borderWidth);
			}
			// Draw bottom border for the last row
			$transcript_pdf->SetX($tableX);
			$transcript_pdf->Cell($tableWidth, 0, '', 'T');
			$start = 0;
		}
		$transcript_pdf_filename = 'transcript_' . $this->user_id . '_' . $this->course_id . '_' . uniqid( '', true ) . '.pdf';
		$transcript_pdf_filepath = WP_CONTENT_DIR . '/uploads/sa-transcript-pdf/' . $transcript_pdf_filename;
		$transcript_pdf_db = wp_upload_dir()['baseurl'] . '/sa-transcript-pdf/' . $transcript_pdf_filename;
		$this->transcript_filepath = $transcript_pdf_filepath;
		$this->transcript_db_filepath = $transcript_pdf_db;
		return true;
	}
	public function AddTranscriptAutomation(): bool {
		global $wpdb;
		$data = array(
			'user_name' => $this->user_name,
			'user_id' => $this->user_id,
			'course_id' => $this->course_id,
			'cert_id' =>  "DUMMY",
			'pdf_path' => $this->transcript_db_filepath,
			'pdf_type' => 'transcript',
			'assigned_date' => current_time('mysql')
		);
		$result = $wpdb->insert($this->table_name, $data);

		if ( ! $result ) {
			$this->error_messages[] = $wpdb->last_error;
			return false;
		}
		return true;
	}
	public function SendUserNotification() {
		$to = $this->user_email;
		$attachments = [];
		$what_purchased = '';
		if ( $this->filepath ) {
			$attachments[] = $this->filepath;
			$what_purchased = ' certificate';
		}

		if ( $this->transcript_filepath ) {
			$attachments[] = $this->transcript_filepath;
			if ($what_purchased) {
				$what_purchased .= ' & transcript';
			} else {
				$what_purchased = ' transcript';
			}
		}

		$subject = 'Your'.$what_purchased.' is ready.';
		$message = 'Dear ' . $this->user_name . ',<br><br>';
		$message .= 'Please find your'.$what_purchased.'attached.<br>';
		$message .= 'Thank you!<br>';

		$headers = array('Content-Type: text/html; charset=UTF-8');

		return wp_mail($to, $subject, $message, $headers, $attachments);
	}
	public function SendAdminNotification() {
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
//		$admin_email = get_option('admin_email');
		$admin_email   = 'shahfayez@staffasia.org';
		$admin_subject = 'Error in Certificate Generation';
		$admin_message = 'User: ' . $this->user_name . ',<br><br>';
		$admin_message .= 'Course: ' . $this->course_id . ',<br><br>';
		$admin_message .= 'Error messages:<br>';
		$admin_message .= implode( '<br>', $this->error_messages );

		return wp_mail( $admin_email, $admin_subject, $admin_message, $headers );
	}
	public function GetCurriculum(): bool {
		$units = bp_course_get_curriculum_units($this->course_id);
		if ($units && is_array($units)) {
			$curriculums = array();
			$key = 1;
			foreach ($units as $unitID) {
				if( get_post_type($unitID) === 'unit'){
					$unitTitle =get_the_title($unitID) ;
					$moduleNumber = 'Module ' . ($key);
					$curriculums[$moduleNumber] = $unitTitle;
					$key++;
				}
			}
			$this->curriculums = $curriculums;
			return true;
		}
		$this->error_messages[] = "Curricular not found";
		return false;
	}
	public function buildResponse($success): array {
		return [
			'success' => $success,
			'errors' => $this->error_messages,
			'certificate' => $this->certificate_status,
			'transcript' => $this->transcript_status,
		];
	}
	public function GetUserCertificates(): array|object|bool {
		$user_id = get_current_user_id();
		global $wpdb;
		$pdf_type = 'certificate';
		$table_name = 'wp_sa_certificate_automation';
		$query = $wpdb->prepare(
			"SELECT * FROM $table_name WHERE user_id = %d AND pdf_type = %s ORDER BY assigned_date DESC",
			$user_id,
			$pdf_type
		);
		$results = $wpdb->get_results($query, ARRAY_A);
		if($results){
			return $results;
		}
		return false;
	}
	public function GetCertificate($course_id): array|object|bool {
		$user_id = get_current_user_id();
		global $wpdb;
		$table_name = 'wp_sa_certificate_automation';
		$pdf_type = 'certificate';
		$query = $wpdb->prepare(
			"SELECT * FROM $table_name WHERE user_id = %d AND course_id = %d AND pdf_type = %s ORDER BY assigned_date DESC",
			$user_id,
			$course_id,
			$pdf_type
		);
		$result = $wpdb->get_results($query, ARRAY_A);
		if($result){
			return $result;
		}
		return false;
	}



//	public function GetCertificate($course_id): array|object|bool {
//		$user_id = get_current_user_id();
//		global $wpdb;
//		$table_name = 'wp_sa_certificate_automation';
//		$pdf_type = 'certificate';
//		$query = $wpdb->prepare(
//			"SELECT assigned_date FROM $table_name WHERE user_id = %d AND course_id = %d AND pdf_type = %s ORDER BY assigned_date DESC",
//			$user_id,
//			$course_id,
//			$pdf_type
//		);
//		$result = $wpdb->get_results($query, ARRAY_A);
//		$url = bp_get_course_certificate('user_id='.$user_id.'&course_id='.$course_id);
//		if($result){
//			return $result[0]['assigned_date'];
//		}
//		if($url){
//			return $url;
//		}
//		return false;
//	}
	public function GetUserTranscripts(): array|object|bool {
		$user_id = get_current_user_id();
		global $wpdb;
		$pdf_type = 'transcript';
		$table_name = 'wp_sa_certificate_automation';
		$query = $wpdb->prepare(
			"SELECT * FROM $table_name WHERE user_id = %d AND pdf_type = %s ORDER BY assigned_date DESC",
			$user_id,
			$pdf_type
		);
		$results = $wpdb->get_results($query, ARRAY_A);
		if($results){
			return $results;
		}
		return false;
	}
}