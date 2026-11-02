		<div class="alert  alert-dismissible fade " style="position:fixed;top:0;right:0;width:250px;" role="alert" id="notify"></div>
		<div class="modal" id="modal-settings" data-target="dashboard" aria-labelledby="modal-settingsLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header"><h5 class="modal-title">Ayarlar</h5> <button type="button" class="close" data-dismiss="modal"> <span>×</span> </button></div>
					<form id="set" action="dashboard.php" method="POST" >
						<input type="hidden" name="xa" value="2">
						<input type="hidden" id="editrecordSid" name="editrecordSid" >
						<div class="modal-body">
							<div class="form-group row">
								<label for="hi_level" class="col-4 col-form-label">Üst Seviye</label>
								<div class="col-2" id="hi_level_label"></div>
								<div class="col-6">
									<input type="range" value="" min="20" max="100"  class="custom-range" id="hi_level" name="hi_level"   >
								</div>
							</div>
							<div class="form-group row">
								<label for="lo_level" class="col-4 col-form-label">Alt Seviye </label>
								<div class="col-2" id="lo_level_label"></div>
								<div class="col-6"><input type="range" value="" min="20" max="100"  class="custom-range " id="lo_level" name="lo_level" > </div>
							</div>
						</div>
						<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button> <button type="submit" class="btn btn-primary">Kaydet</button> </div>
					</form>
				</div>
			</div>
		</div>
				<!-- Footer -->
		<div class="py-3 pagefooter" >
			<div class="container">
				<div class="row ">
					<div class="col-6 text-left  ">
						<p class="mb-0 py-1">Developed by <a href="http://www.pievision.com" target="_blank"><img src="assets/img/pievision-logo.png?t=<?php echo date('His')?>"></a></p>
					</div>
					<div class="col-6 text-right  ">
						<p class="mb-0 py-1">&copy; 2020 Turhal Belediyesi</p>
					</div>
				</div>
			</div>
		</div>
		<!-- /Footer -->
	</body>
</html>