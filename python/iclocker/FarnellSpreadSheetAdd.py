import gspread
import json
from oauth2client.client import SignedJwtAssertionCredentials

import wx
import wx.lib.newevent
#import wx.lib.intctrl
import threading
import time

AddedToFarnellSpreadsheetEvent, EVT_ADDED_TO_SPREEADSHEAT_EVENT = wx.lib.newevent.NewEvent()

json_key_str = """{
  "private_key_id": "eb8ea4fc798b281ed3d20c90a27bde44ca4db51a",
  "private_key": "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDnWy+3Z5Q306Zu\nAQlMGf537NCGep4puHOCvtT3nJnEePLDQcE9v3NXuLnrgKupBPINf1jqwJgb8rxK\nyAJzoi9EJRIOsHBYaLAxwE+VYrhbVBUCPvsmrgIX4Blj5F6NDsegF/u9hyqRAgli\nMSTDLk1N2MjZip4QoWa4Ye8CGT65osXz+BqLUec9Hmiw/6EhmnED1quacwHeLEGS\nf/rtjdPZkCM5cgA86sLGXpH0YBc2I3MDHDt2ZbQcBBZ4q7imfkf87SpXKP4oHLe/\nUDah2g+bR+k2tutUlYrDJWcKrvLmq5fTiTJqgiF8S1PZPlSTBmP3vbpdU/y6NMFk\nG3azK9BBAgMBAAECggEAOj2YS4FFrLAZK4QTRRceGi6BZ/kiK7qFSZqknhjMFMul\nwUWiUdsKPEECfKiNjiTykjdGuQH2yMJYaDLlexqO74atfwknnvnrsPqKjj6lzkfi\naghUzS3s1PnTKnRo4PWhh0nCU7ndG1f56MAaUxAq4bf7B9h/ZgxbgXsV4d9dDPnU\ndqVQwvkWzf8fnfvzUuar7kQZ1VuA4ST75Wjki9+ak5KNccGftofEYQWAm7lVYFPU\nq8BW9x15rMUhlpEFKa+u8XNWvquLeKMO/V9W44MByGPYl2iSv1Jm6KN2FQXZtyld\n2ipZFQjwcbNIl6Xh+ynJMsV9oV1qOURbTkzDibzlYQKBgQD1V3sJYXEy3F6HehI6\nxVnkAElue2dlOk01/wj9ateY3nIjNJ8JB325+tMzxJ/tpc5UFfxAcffpHpaGfmJJ\nGRut1S/n9uz+9zXBUh2BxowgoGN75Ldb/cubuvFJLDanmUR+9x3yqX/LM/zl+Glb\n7grugNFqPbZjr5nzdWgDbOlRBQKBgQDxaCsldJQ9eDZfsTVRBtiyrLfI8CnF96pV\nTo6TrCvHy5Yl8Au0RA0QG12pFW9FQF650uKtWPV2K4g3Ed0Bxhowny9eVmRetge1\nlvb+LUxMtlgDgXZbHD2gCMGm4Hz4BbJ+gTzdYrI/ByuK2YAhQHdvL+AcQJavAMIF\nyJcOf8JXDQKBgDX8YvmEdJlBfpeHF/3QsWAHZCEojG7s7lKEZSEGYpyjzi/LA3fE\nKzlCZTkN+jcb9hPwpoozyd6FOZAsvUnieYG92IXNgwbztONuQ0nsO6duQ5XelS5r\n4WLKNw/n57rncfgSPofIHnPDY9Hi4KQ29DbZJ7ueCsVSvaih3Bps28ZVAoGANrf8\n562IGHLSKFiblDUwEzfxJJvDbDsaIeH/kVt6RPxRmWHS1VaDe34oebYBFbpkfkxd\n0xsR8Gonowvqg4dq0lCSxRhiAdHS54dDjxOncs/2HT1QHZDGKysw8el6iiGIdwJG\nUIwZiQ3QAdYRI/hf2hUJAH5naV2LnRH9o2y0GVUCgYEAxK1CZkrGBCI9TFFUz9jN\nWyqFeIdpvQhrIqIqQ0YTtroqA0oYJ1bphfO91CtSb0zQtTzU/KkRw8qKBuc/OYLP\nr53HucutqztYU3Mxh1LgsUF4QBu4vgZPGe/OToYwdqcVYy7wJ5s3m8BY7wkZOebW\nXYG+znAF9iFA4KxNqxJ3E94\u003d\n-----END PRIVATE KEY-----\n",
  "client_email": "627302655632-gn7oe2q30p6qbm8mij692v0k8nl1oigh@developer.gserviceaccount.com",
  "client_id": "627302655632-gn7oe2q30p6qbm8mij692v0k8nl1oigh.apps.googleusercontent.com",
  "type": "service_account"
}"""

json_key = {u'type': u'service_account', u'private_key': u'-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDnWy+3Z5Q306Zu\nAQlMGf537NCGep4puHOCvtT3nJnEePLDQcE9v3NXuLnrgKupBPINf1jqwJgb8rxK\nyAJzoi9EJRIOsHBYaLAxwE+VYrhbVBUCPvsmrgIX4Blj5F6NDsegF/u9hyqRAgli\nMSTDLk1N2MjZip4QoWa4Ye8CGT65osXz+BqLUec9Hmiw/6EhmnED1quacwHeLEGS\nf/rtjdPZkCM5cgA86sLGXpH0YBc2I3MDHDt2ZbQcBBZ4q7imfkf87SpXKP4oHLe/\nUDah2g+bR+k2tutUlYrDJWcKrvLmq5fTiTJqgiF8S1PZPlSTBmP3vbpdU/y6NMFk\nG3azK9BBAgMBAAECggEAOj2YS4FFrLAZK4QTRRceGi6BZ/kiK7qFSZqknhjMFMul\nwUWiUdsKPEECfKiNjiTykjdGuQH2yMJYaDLlexqO74atfwknnvnrsPqKjj6lzkfi\naghUzS3s1PnTKnRo4PWhh0nCU7ndG1f56MAaUxAq4bf7B9h/ZgxbgXsV4d9dDPnU\ndqVQwvkWzf8fnfvzUuar7kQZ1VuA4ST75Wjki9+ak5KNccGftofEYQWAm7lVYFPU\nq8BW9x15rMUhlpEFKa+u8XNWvquLeKMO/V9W44MByGPYl2iSv1Jm6KN2FQXZtyld\n2ipZFQjwcbNIl6Xh+ynJMsV9oV1qOURbTkzDibzlYQKBgQD1V3sJYXEy3F6HehI6\nxVnkAElue2dlOk01/wj9ateY3nIjNJ8JB325+tMzxJ/tpc5UFfxAcffpHpaGfmJJ\nGRut1S/n9uz+9zXBUh2BxowgoGN75Ldb/cubuvFJLDanmUR+9x3yqX/LM/zl+Glb\n7grugNFqPbZjr5nzdWgDbOlRBQKBgQDxaCsldJQ9eDZfsTVRBtiyrLfI8CnF96pV\nTo6TrCvHy5Yl8Au0RA0QG12pFW9FQF650uKtWPV2K4g3Ed0Bxhowny9eVmRetge1\nlvb+LUxMtlgDgXZbHD2gCMGm4Hz4BbJ+gTzdYrI/ByuK2YAhQHdvL+AcQJavAMIF\nyJcOf8JXDQKBgDX8YvmEdJlBfpeHF/3QsWAHZCEojG7s7lKEZSEGYpyjzi/LA3fE\nKzlCZTkN+jcb9hPwpoozyd6FOZAsvUnieYG92IXNgwbztONuQ0nsO6duQ5XelS5r\n4WLKNw/n57rncfgSPofIHnPDY9Hi4KQ29DbZJ7ueCsVSvaih3Bps28ZVAoGANrf8\n562IGHLSKFiblDUwEzfxJJvDbDsaIeH/kVt6RPxRmWHS1VaDe34oebYBFbpkfkxd\n0xsR8Gonowvqg4dq0lCSxRhiAdHS54dDjxOncs/2HT1QHZDGKysw8el6iiGIdwJG\nUIwZiQ3QAdYRI/hf2hUJAH5naV2LnRH9o2y0GVUCgYEAxK1CZkrGBCI9TFFUz9jN\nWyqFeIdpvQhrIqIqQ0YTtroqA0oYJ1bphfO91CtSb0zQtTzU/KkRw8qKBuc/OYLP\nr53HucutqztYU3Mxh1LgsUF4QBu4vgZPGe/OToYwdqcVYy7wJ5s3m8BY7wkZOebW\nXYG+znAF9iFA4KxNqxJ3E94=\n-----END PRIVATE KEY-----\n', u'client_email': u'627302655632-gn7oe2q30p6qbm8mij692v0k8nl1oigh@developer.gserviceaccount.com', u'private_key_id': u'eb8ea4fc798b281ed3d20c90a27bde44ca4db51a', u'client_id': u'627302655632-gn7oe2q30p6qbm8mij692v0k8nl1oigh.apps.googleusercontent.com'}


FARNELL_STRINGS = ["Farnell Number","Minimum quantity","Desired quantity","Purpose","Description"]

FARNELL_PURPOSES = ["","RazvojOstalo","BrezzicniModuli","Ecg","WirelessCuff","Spiro","MainUnit","Dock","Bluetooth","ABPI","Proizvodnja","Miha","Martin","Tomo","Tilen"]

FARNELL_START_ROW = 4

class FarnellListSpreadsheetAdd(threading.Thread):
	def __init__(self, wx, farnell_number, min_quantity, quantity, purpose, description):
		threading.Thread.__init__(self)
		self._stop = threading.Event()
		self.wx = wx

		self.farnell_number = farnell_number
		self.min_quantity = min_quantity
		self.quantity = quantity
		self.purpose = purpose
		self.description = description

		self.start()

	def run(self):
		print "adding...."
		#json_key = json.load(open('API Project-eb8ea4fc798b.json'))
		#print json_key
		scope = ['https://spreadsheets.google.com/feeds']

		credentials = SignedJwtAssertionCredentials(json_key['client_email'], json_key['private_key'], scope)
		gc = gspread.authorize(credentials)

		self.spreadSheat = gc.open_by_url('https://docs.google.com/spreadsheets/d/1LDvELXzjYp2pRwGsMMThe0DAQNbKaXWIofqonud5JzU/edit?usp=sharing')
		#self.spreadSheat = gc.open("Farnell narocila")
		self.farnellSheat = self.spreadSheat.sheet1

		farnellList = self.farnellSheat.get_all_values()[FARNELL_START_ROW:]
		farnellList.append(['', '', '', '', '', '', ''])
		#print farnellList
		first_emmpty = FARNELL_START_ROW
		for i in range(len(farnellList)):
			if farnellList[i][1] == '':
				break
		first_emmpty = FARNELL_START_ROW+i+1

		#print first_emmpty
		self.farnellSheat.insert_row(["",str(self.farnell_number),str(self.min_quantity),str(self.quantity),str(self.purpose),"",str(self.description)],index=first_emmpty)

		#self.farnellSheat.update_cell(first_emmpty, 2, str(self.farnell_number))
		#self.farnellSheat.update_cell(first_emmpty, 3, str(self.min_quantity))
		#self.farnellSheat.update_cell(first_emmpty, 4, str(self.quantity))
		#self.farnellSheat.update_cell(first_emmpty, 5, str(self.purpose))
		#self.farnellSheat.update_cell(first_emmpty, 7, str(self.description))

		evt = AddedToFarnellSpreadsheetEvent(succses = True)
		wx.PostEvent(self.wx, evt)
		print "added"

class FarnellAddFrame(wx.Frame):
	def __init__(self, parent, num = "", purpose = "", description = ""):
		self.l = wx.Locale(wx.LANGUAGE_ENGLISH)
		wx.Frame.__init__(self, parent, title="Farnell Add", style = wx.SYSTEM_MENU | wx.CAPTION | wx.CLOSE_BOX | wx.MINIMIZE_BOX | wx.MAXIMIZE_BOX)

		self.num = num
		self.purpose = purpose
		self.description = description

		self.create_main_panel()

		self.Bind(EVT_ADDED_TO_SPREEADSHEAT_EVENT, self.OnAddedToFarnellSpreadsheetEvent)

		self.Show(True)


	def create_main_panel(self):
		self.panel = wx.Panel(self,wx.ID_ANY)
		self.SetBackgroundColour(wx.WHITE)
		self.font = wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_NORMAL)
		self.fontB = wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_BOLD)
		self.fontS = wx.Font(10, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_NORMAL)


		self.farnell_st = []
		self.farnell_tc = []
		self.farnell_box = []
		for i in range(len(FARNELL_STRINGS)):
			self.farnell_st.append(wx.StaticText(self.panel, label=FARNELL_STRINGS[i], size=(100,40)))
			self.farnell_st[-1].SetFont(self.font)
			if i == 0:
				self.farnell_tc.append(wx.TextCtrl(self.panel, size=(100,30), style=wx.TE_RICH2))
			elif i>=1 and i<=2:
				self.farnell_tc.append(wx.SpinCtrl(self.panel, size=(100,30), style=wx.TE_RICH2))
			elif i == 3:
				if self.purpose:
					FARNELL_PURPOSES.append(self.purpose)
					self.farnell_tc.append(wx.Choice(self.panel, choices=FARNELL_PURPOSES, size=(120,30)))
					self.farnell_tc[-1].SetSelection(len(FARNELL_PURPOSES)-1)
				else:
					self.farnell_tc.append(wx.Choice(self.panel, choices=FARNELL_PURPOSES, size=(120,30)))
			else:
				self.farnell_tc.append(wx.TextCtrl(self.panel, size=(200,30), style=wx.TE_RICH2))
			self.farnell_tc[-1].SetFont(self.font)
			self.farnell_box.append(wx.BoxSizer(wx.VERTICAL))
			self.farnell_box[-1].Add(self.farnell_st[-1], wx.CENTER)
			#self.farnell_box[-1].AddSpacer(5)
			self.farnell_box[-1].Add(self.farnell_tc[-1])


		self.farnell_tc[0].AppendText(self.num)
		self.farnell_tc[4].AppendText(self.description)

		self.add_button =wx.Button(self.panel, label="Add to Farnell\r\n spreadsheet",size=(150,70))
		self.add_button.Bind(wx.EVT_BUTTON, self.OnAdd)
		self.add_button.SetFont(self.font)


		#definiranje vseh saizerjev
		self.mainSizer = wx.BoxSizer(wx.VERTICAL)
		self.hbox = wx.BoxSizer(wx.HORIZONTAL)

		self.hbox.AddSpacer(20)
		for i in range(len(FARNELL_STRINGS)):
			self.hbox.Add(self.farnell_box[i],0,wx.EXPAND)
			self.hbox.AddSpacer(5)
		
		self.hbox.Add(self.add_button,0,wx.CENTER)
		self.hbox.AddSpacer(20)

		#celotna oblika
		self.mainSizer.AddSpacer(30)
		self.mainSizer.Add(self.hbox,1,wx.EXPAND)
		self.mainSizer.AddSpacer(30)

		self.panel.SetSizerAndFit(self.mainSizer)
		self.mainSizer.Fit(self)

	def OnAdd(self, event):
		print "***"
		a = FarnellListSpreadsheetAdd(self, self.farnell_tc[0].GetValue(), self.farnell_tc[1].GetValue(), self.farnell_tc[2].GetValue(), self.farnell_tc[3].GetString(self.farnell_tc[3].GetCurrentSelection()), self.farnell_tc[4].GetValue())

	def OnAddedToFarnellSpreadsheetEvent(self,event):
		self.Destroy()
		pass
		

if __name__ == '__main__':
	app = wx.App(False)
	app.frame = FarnellAddFrame(None)
	app.frame.Show()
	app.SetTopWindow(app.frame)

	app.MainLoop()