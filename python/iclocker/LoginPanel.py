import wx
import os

LOGONAME = "logo.png"
LOGOSIZE = (65,65)

LOGIN_HEADER_PANEL_COLOR = '#41454A'
LOGIN_MAIN_PANEL_COLOR = '#58606A'
LOGIN_FOOTER_PANEL_COLOR = '#41454A'

LOGIN_HEADER_PANEL_MINSIZE = 80
LOGIN_MAIN_PANEL_MINSIZE = 80
LOGIN_FOOTER_PANEL_MINSIZE = 80

LOGIN_HEADER_TITLE = 'IC Locker'
LOGIN_HEADER_TITLE_FONT = [24, wx.FONTFAMILY_DEFAULT, wx.FONTSTYLE_NORMAL, wx.FONTWEIGHT_BOLD]
LOGIN_HEADER_TITLE_COLOR = '#FFFFFF'

LOGIN_TITLE = 'Log in to application'
LOGIN_TITLE_FONT = [24, wx.FONTFAMILY_DEFAULT, wx.FONTSTYLE_NORMAL, wx.FONTWEIGHT_NORMAL]
LOGIN_TITLE_COLOR = '#FFFFFF'

LOGIN_USERNAME = 'Username'
LOGIN_PASSWORD = 'Password'
LOGIN_USERNAMEPASSWORD_FONT = [16, wx.FONTFAMILY_DEFAULT, wx.FONTSTYLE_NORMAL, wx.FONTWEIGHT_NORMAL]
LOGIN_USERNAMEPASSWORD_COLOR = '#FFFFFF'
LOGIN_USERNAMEPASSWORD_INPUT_COLOR = '#000000'

LOGIN_REMEMBER = 'Remember me'
LOGIN_REMEMBER_FONT = [16, wx.FONTFAMILY_DEFAULT, wx.FONTSTYLE_NORMAL, wx.FONTWEIGHT_NORMAL]
LOGIN_REMEMBER_COLOR = '#FFFFFF'

LOGIN_BUTTON = 'Login'
LOGIN_BUTTON_FONT = [16, wx.FONTFAMILY_DEFAULT, wx.FONTSTYLE_NORMAL, wx.FONTWEIGHT_NORMAL]
LOGIN_BUTTON_COLOR = '#000000'

LOGIN_FOOTER = 'IC Locker 2016 All rights reserved!'
LOGIN_FOOTER_FONT = [12, wx.FONTFAMILY_DEFAULT, wx.FONTSTYLE_NORMAL, wx.FONTWEIGHT_NORMAL]
LOGIN_FOOTER_COLOR = '#FFFFFF'


def setTextStyle(text, font, color):
	text.SetFont(wx.Font(font[0], 
		family = font[1], 
		style = font[2], 
		weight = font[3]))
	text.SetForegroundColour(color)

class LoginPanel(wx.Panel):
	def __init__(self, parent):
		wx.Panel.__init__(self, parent)
		self.panel = self

		self.parent = parent
		
		self.elementBaseThread = self.parent.elementBaseThread

		self.create_upper_panel()
		self.create_main_panel()
		self.create_lower_panel()
		self.combine_panels()

		self.CheckLogin()

	def create_upper_panel(self):
		self.upperPanel = wx.Panel(self.panel)
		self.upperPanel.BackgroundColour = (LOGIN_HEADER_PANEL_COLOR)

		image = wx.ImageFromBitmap(self.parent.logo)
		image = image.Scale(LOGOSIZE[0],LOGOSIZE[1], wx.IMAGE_QUALITY_HIGH)
		self.logoIcon  = wx.StaticBitmap(self.upperPanel, wx.ID_ANY, wx.BitmapFromImage(image))

		self.siteName = wx.StaticText(self.upperPanel, label=LOGIN_HEADER_TITLE)
		setTextStyle(self.siteName, LOGIN_HEADER_TITLE_FONT, LOGIN_HEADER_TITLE_COLOR)

		self.upperSizer = wx.BoxSizer(wx.HORIZONTAL)

		self.upperSizer.Add((0,0),1,wx.EXPAND)
		self.upperSizer.Add(self.logoIcon,0,wx.EXPAND)
		self.upperSizer.AddSpacer(20)
		self.upperSizer.Add(self.siteName,0,wx.CENTER)
		self.upperSizer.Add((0,0),5,wx.EXPAND)

		self.upperSizer.SetMinSize((0,LOGIN_HEADER_PANEL_MINSIZE))

		self.upperPanel.SetSizerAndFit(self.upperSizer)
		self.upperSizer.Fit(self)

	def create_lower_panel(self):
		self.lowerPanel = wx.Panel(self.panel)
		self.lowerPanel.BackgroundColour = (LOGIN_FOOTER_PANEL_COLOR)
		
		self.footerStr = wx.StaticText(self.lowerPanel, label=LOGIN_FOOTER)
		setTextStyle(self.footerStr, LOGIN_FOOTER_FONT, LOGIN_FOOTER_COLOR)

		self.lowerSizer = wx.BoxSizer(wx.HORIZONTAL)

		self.lowerSizer.Add((0,0),5,wx.EXPAND)
		self.lowerSizer.Add(self.footerStr,0,wx.EXPAND)
		self.lowerSizer.Add((0,0),1,wx.EXPAND)

		self.lowerSizer.SetMinSize((0,LOGIN_FOOTER_PANEL_MINSIZE)) 

		self.lowerPanel.SetSizerAndFit(self.lowerSizer)
		self.lowerSizer.Fit(self)

	def create_main_panel(self):
		self.mainPanel = wx.Panel(self.panel)
		self.mainPanel.SetBackgroundColour(LOGIN_MAIN_PANEL_COLOR)

		self.siteTitle = wx.StaticText(self.mainPanel, label=LOGIN_TITLE)
		setTextStyle(self.siteTitle, LOGIN_TITLE_FONT, LOGIN_TITLE_COLOR)

		self.username_st = wx.StaticText(self.mainPanel, label=LOGIN_USERNAME)
		setTextStyle(self.username_st, LOGIN_USERNAMEPASSWORD_FONT, LOGIN_USERNAMEPASSWORD_COLOR)

		self.username_tc = wx.TextCtrl(self.mainPanel, style=wx.TE_RICH2)
		setTextStyle(self.username_tc, LOGIN_USERNAMEPASSWORD_FONT, LOGIN_USERNAMEPASSWORD_INPUT_COLOR)

		self.password_st = wx.StaticText(self.mainPanel, label=LOGIN_PASSWORD)
		setTextStyle(self.password_st, LOGIN_USERNAMEPASSWORD_FONT, LOGIN_USERNAMEPASSWORD_COLOR)

		self.password_tc = wx.TextCtrl(self.mainPanel, style=wx.TE_RICH2 | wx.TE_PASSWORD)
		setTextStyle(self.password_tc, LOGIN_USERNAMEPASSWORD_FONT, LOGIN_USERNAMEPASSWORD_INPUT_COLOR)


		self.description_box = wx.BoxSizer(wx.VERTICAL)
		self.description_box.Add(self.username_st,0,wx.EXPAND)
		self.description_box.AddSpacer(5)
		self.description_box.Add(self.password_st,0,wx.EXPAND)

		self.rememberCheck = wx.CheckBox(self.mainPanel, id=wx.ID_ANY, label=LOGIN_REMEMBER)
		setTextStyle(self.rememberCheck, LOGIN_REMEMBER_FONT, LOGIN_REMEMBER_COLOR)

		self.login_button = wx.Button(self.mainPanel, label=LOGIN_BUTTON,size=(150,30))
		self.login_button.Bind(wx.EVT_BUTTON, self.OnLogin)
		setTextStyle(self.login_button, LOGIN_BUTTON_FONT, LOGIN_BUTTON_COLOR)
		#self.login_button.SetFont(self.font)

		self.input_box = wx.BoxSizer(wx.VERTICAL)
		self.input_box.Add(self.username_tc,0,wx.EXPAND)
		self.input_box.AddSpacer(5)
		self.input_box.Add(self.password_tc,0,wx.EXPAND)
		self.input_box.AddSpacer(10)
		self.input_box.Add(self.rememberCheck,0)
		self.input_box.AddSpacer(10)
		self.input_box.Add(self.login_button,0)

		self.login_box = wx.BoxSizer(wx.HORIZONTAL)
		self.login_box.Add((0,0),1,wx.EXPAND)
		self.login_box.Add(self.description_box)
		self.login_box.AddSpacer(10)
		self.login_box.Add(self.input_box,1,wx.EXPAND)
		self.login_box.Add((0,0),1,wx.EXPAND)

		self.middleSizer = wx.BoxSizer(wx.VERTICAL)
		self.middleSizer.Add((0,0),1,wx.EXPAND)
		self.middleSizer.AddSpacer(40)
		self.middleSizer.Add(self.siteTitle,0,wx.CENTER)
		self.middleSizer.AddSpacer(40)
		self.middleSizer.Add(self.login_box,0,wx.EXPAND)
		self.middleSizer.AddSpacer(10)
		self.middleSizer.AddSpacer(30)
		self.middleSizer.Add((0,0),2,wx.EXPAND)

		self.mainPanel.SetSizerAndFit(self.middleSizer)
		self.middleSizer.Fit(self)

	def combine_panels(self):
		self.combinedSizer = wx.BoxSizer(wx.VERTICAL)
		self.combinedSizer.Add(self.upperPanel,0,wx.EXPAND)
		self.combinedSizer.Add(self.mainPanel,1,wx.EXPAND)
		self.combinedSizer.Add(self.lowerPanel,0,wx.EXPAND)

		self.panel.SetSizer(self.combinedSizer)
		self.panel.Layout()

	def CheckLogin(self):
		appDataPATH = os.getenv('APPDATA')
		self.loginFilePath = appDataPATH +'\\IcLocker\\'
		if not(os.path.exists(self.loginFilePath)):
			os.makedirs(self.loginFilePath)

		try:
			loginFile = open(self.loginFilePath+"IcLocker.login",'r')
			lines = loginFile.readlines()
			loginFile.close()
			if len(lines) >= 1:
				self.username_tc.SetValue(lines[0].strip("\r\n"))
				self.password_tc.SetValue(lines[1].strip("\r\n"))
		except Exception, e:
			print e
			print "login file doesn't exist"

	def OnLogin(self, event):
		username = self.username_tc.GetLineText(0)
		password = self.password_tc.GetLineText(0)

		if self.rememberCheck.IsChecked():
			#TODO: encryption
			loginFile = open(self.loginFilePath+"IcLocker.login",'w')
			loginFile.write(username+"\n")
			loginFile.write(password+"\n")
			loginFile.close()
		
		if self.elementBaseThread != None:
			self.elementBaseThread.loginFlag = [username, password]


class LoginTestFrame(wx.Frame):
	def __init__(self, parent, title, size, style):
		self.l = wx.Locale(wx.LANGUAGE_ENGLISH)
		wx.Frame.__init__(self, parent, title=title, size = size, style =style)
		
		self.logo = wx.Bitmap(LOGONAME)

		self.elementBaseThread = None

		self.Show(True)

		self.loginPanel = LoginPanel(self)
		self.loginPanel.Show()

		self.sizer = wx.BoxSizer(wx.VERTICAL)
		self.sizer.Add(self.loginPanel, 1, wx.EXPAND | wx.ALL)
		self.SetSizer(self.sizer)

		self.loginPanel.SetSizerAndFit(self.loginPanel.combinedSizer)

		self.Layout()


if __name__ == '__main__':
	app = wx.App(False)
	app.frame = LoginTestFrame(None, "IC Locker (only LoginPanel)",
		(1300,700),
		style = wx.SYSTEM_MENU | wx.CAPTION | wx.CLOSE_BOX | wx.MINIMIZE_BOX | wx.MAXIMIZE_BOX | wx.RESIZE_BORDER)
	app.frame.Show()
	app.SetTopWindow(app.frame)

	app.MainLoop()
		