import os
import wx
import time

import wx.lib.scrolledpanel

LOGONAME = "logo.png"


class AltiumToBaseFrame(wx.Frame):
	def __init__(self, parent, title):
		self.l = wx.Locale(wx.LANGUAGE_ENGLISH)
		wx.Frame.__init__(self, parent, title=title, style = wx.SYSTEM_MENU | wx.CAPTION | wx.CLOSE_BOX | wx.MINIMIZE_BOX | wx.MAXIMIZE_BOX | wx.RESIZE_BORDER)
		self.statusBar = self.CreateStatusBar() # A StatusBar in the bottom of the window

		self.SetSizeHints(500,300,1200,800)

		self.parent = parent

		self.currentDirectory = os.getcwd()

		self.wx_elements =[]
		self.hboxs = []


		self.BaseElement_name = []

		if __name__ == '__main__':
			self.BaseElements = {0: {'category': u'Diode', 'comment': u'Dual Schottky barrier diode', 'farnell': '', 'name': u'1PS70SB14', 'datasheet': u'http://www.nxp.com/documents/data_sheet/1PS70SB14.pdf', 'quantity': 12, 'category_id': u'Diode', 'number': 0, 'id': 42}, 1: {'category': u'NMOS transistor', 'comment': u'60 V, single N-channel Trench MOSFET', 'farnell': u'2191746', 'name': u'2N7002', 'datasheet': u'http://www.nxp.com/documents/data_sheet/NX7002AK.pdf', 'quantity': 51, 'category_id': u'NMOS transistor', 'number': 1, 'id': 7}, 2: {'category': u'Trafo/Isolator', 'comment': u'3-5V, MAX253 Compatible Converter Transformers', 'farnell': '', 'name': u'7825335MVC', 'datasheet': u'http://www.murata-ps.com/data/magnetics/kmp_78253.pdf', 'quantity': 4, 'category_id': u'Trafo/Isolator', 'number': 2, 'id': 50}, 3: {'category': u'Trafo/Isolator', 'comment': u'5-5V, MAX253 Compatible Converter Transformers', 'farnell': u'1362408', 'name': u'7825355MVC', 'datasheet': u'http://www.murata-ps.com/data/magnetics/kmp_78253.pdf', 'quantity': 2, 'category_id': u'Trafo/Isolator', 'number': 3, 'id': 127}, 4: {'category': u'Connector', 'comment': u'dual square 2mm pinhader 16Way', 'farnell': u'7472153', 'name': u'87760-1616', 'datasheet': u'http://www.farnell.com/datasheets/1946296.pdf', 'quantity': 3, 'category_id': u'Connector', 'number': 4, 'id': 163}, 5: {'category': u'Memory', 'comment': u'1M X 16 Bit X 4 Banks Synchronous DRAM', 'farnell': u'1907102', 'name': u'A43L2616BV-7F', 'datasheet': u'http://www.farnell.com/datasheets/1383066.pdf', 'quantity': 0, 'category_id': u'Memory', 'number': 5, 'id': 96}, 6: {'category': u'DAC', 'comment': u'2.5 V to 5.5 V, 500 \u03bcA, Quad Voltage Output 8-/10-/12-Bit DACs in 10-Lead Packages', 'farnell': u'9425926', 'name': u'AD5314', 'datasheet': u'http://www.analog.com/media/en/technical-documentation/data-sheets/AD5304_5314_5324.pdf', 'quantity': 10, 'category_id': u'DAC', 'number': 6, 'id': 78}, 7: {'category': u'Frontend', 'comment': u'Single-Lead, Heart Rate Monitor Front End', 'farnell': '', 'name': u'AD8232ACPZ', 'datasheet': u'http://www.analog.com/media/en/technical-documentation/data-sheets/AD8232.pdf', 'quantity': 4, 'category_id': u'Frontend', 'number': 7, 'id': 31}, 8: {'category': u'OP AMP', 'comment': u'Precision CMOS, Single-Supply, Rail-to-Rail, Input/Output Wideband Operational Amplifiers', 'farnell': u'1651307', 'name': u'AD8601', 'datasheet': u'http://www.farnell.com/datasheets/1059675.pdf', 'quantity': 2, 'category_id': u'OP AMP', 'number': 8, 'id': 143}, 9: {'category': u'OP AMP', 'comment': u'2x Precision CMOS, Single-Supply, Rail-to-Rail, Input/Output Wideband Operational Amplifiers', 'farnell': u'1838872', 'name': u'AD8602', 'datasheet': u'http://www.farnell.com/datasheets/1059675.pdf', 'quantity': 2, 'category_id': u'OP AMP', 'number': 9, 'id': 86}, 10: {'category': u'OP AMP', 'comment': u'Precision, Low Noise, CMOS, Rail-to-Rail, Input/Output Operational Amplifiers', 'farnell': u'1838870', 'name': u'AD8605', 'datasheet': u'http://www.analog.com/media/en/technical-documentation/data-sheets/AD8605_8606_8608.pdf', 'quantity': 24, 'category_id': u'OP AMP', 'number': 10, 'id': 76}, 11: {'category': u'OP AMP', 'comment': u'4x Precision, Low Noise, CMOS, Rail-to-Rail, Input/Output Operational Amplifiers', 'farnell': u'1660999', 'name': u'AD8608', 'datasheet': u'http://www.analog.com/media/en/technical-documentation/data-sheets/AD8605_8606_8608.pdf', 'quantity': 1, 'category_id': u'OP AMP', 'number': 11, 'id': 85}, 12: {'category': u'OP AMP', 'comment': u'4x Precision, 20 MHz, CMOS, Rail-to-Rail\n Input/Output Operational Amplifiers', 'farnell': u'1078278', 'name': u'AD8618', 'datasheet': u'http://www.analog.com/media/en/technical-documentation/data-sheets/AD8615_8616_8618.pdf', 'quantity': 10, 'category_id': u'OP AMP', 'number': 12, 'id': 77}, 13: {'category': u'OP AMP', 'comment': u'10 \u03bcA, Rail-to-Rail I/O, Zero Input Crossover Distortion Amplifiers', 'farnell': u'1827377', 'name': u'ADA4505', 'datasheet': u'http://www.farnell.com/datasheets/608874.pdf', 'quantity': 10, 'category_id': u'OP AMP', 'number': 13, 'id': 79}, 14: {'category': u'OP AMP', 'comment': u'Low Power, Low Noise and Distortion, Rail-to-Rail Output Amplifiers', 'farnell': u'2067793', 'name': u'ADA4841-1YRJZ', 'datasheet': u'http://www.analog.com/media/en/technical-documentation/data-sheets/ADA4841-1_4841-2.pdf', 'quantity': 19, 'category_id': u'OP AMP', 'number': 14, 'id': 89}, 15: {'category': u'OP AMP', 'comment': u'Low Power, Low Noise and Distortion, Rail-to-Rail Output Amplifiers', 'farnell': u'1838875', 'name': u'ADA4841-1YRZ', 'datasheet': u'http://www.analog.com/media/en/technical-documentation/data-sheets/ADA4841-1_4841-2.pdf', 'quantity': 10, 'category_id': u'OP AMP', 'number': 15, 'id': 88}, 16: {'category': u'Frontend', 'comment': u'Low Power, Five Electrode Electrocardiogram (ECG) Analog Front End', 'farnell': '', 'name': u'ADAS1000-2', 'datasheet': u'http://www.analog.com/media/en/technical-documentation/data-sheets/ADAS1000_1000-1_1000-2.pdf', 'quantity': 9, 'category_id': u'Frontend', 'number': 16, 'id': 24}, 17: {'category': u'Frontend', 'comment': u'Low Power, Three Electrode Electrocardiogram (ECG) Analog Front End', 'farnell': '', 'name': u'ADAS1000-3', 'datasheet': u'http://www.analog.com/media/en/technical-documentation/data-sheets/ADAS1000-3_1000-4.pdf', 'quantity': 4, 'category_id': u'Frontend', 'number': 17, 'id': 25}, 18: {'category': u'Frontend', 'comment': u'Low Power, Three Electrode Electrocardiogram (ECG) Analog Front End', 'farnell': '', 'name': u'ADAS1000-4', 'datasheet': u'http://www.analog.com/media/en/technical-documentation/data-sheets/ADAS1000-3_1000-4.pdf', 'quantity': 3, 'category_id': u'Frontend', 'number': 18, 'id': 26}, 19: {'category': u'Frontend', 'comment': u'Low Power, Five Electrode Electrocardiogram (ECG) Analog Front End', 'farnell': '', 'name': u'ADAS1000BTPS', 'datasheet': u'http://www.analog.com/media/en/technical-documentation/data-sheets/ADAS1000_1000-1_1000-2.pdf', 'quantity': 1, 'category_id': u'Frontend', 'number': 19, 'id': 23}, 20: {'category': u'Frontend', 'comment': u'Low Power, Five Electrode Electrocardiogram (ECG) Analog Front End', 'farnell': '', 'name': u'ADAS1000BTPZ', 'datasheet': u'http://www.analog.com/media/en/technical-documentation/data-sheets/ADAS1000_1000-1_1000-2.pdf', 'quantity': 11, 'category_id': u'Frontend', 'number': 20, 'id': 22}, 21: {'category': u'LDO', 'comment': u'Ultralow Noise, 200 mA, CMOS Linear Regulator', 'farnell': '', 'name': u'ADP151AUJZ', 'datasheet': u'http://www.analog.com/media/en/technical-documentation/data-sheets/ADP151.pdf', 'quantity': 10, 'category_id': u'LDO', 'number': 21, 'id': 30}, 22: {'category': u'LDO', 'comment': u'Micropower, High Accuracy Voltage References', 'farnell': u'1827384', 'name': u'ADR3425', 'datasheet': u'http://www.analog.com/media/en/technical-documentation/data-sheets/ADR3412_ADR3420_ADR3425_ADR3430_ADR3433_ADR3440_ADR3450.pdf', 'quantity': 11, 'category_id': u'LDO', 'number': 22, 'id': 52}, 23: {'category': u'ADC', 'comment': u'Ultra-Small, Low-Power, 16-Bit Analog-to-Digital Converter with Internal Reference', 'farnell': u'1762976', 'name': u'ADS1113', 'datasheet': u'http://www.ti.com/lit/ds/symlink/ads1113.pdf', 'quantity': 4, 'category_id': u'ADC', 'number': 23, 'id': 93}, 24: {'category': u'Frontend', 'comment': u'Low-Power, 8-Channel, 16-Bit Analog Front-End for Biopotential Measurements', 'farnell': '', 'name': u'ADS1198', 'datasheet': u'http://www.ti.com/lit/ds/symlink/ads1196.pdf', 'quantity': 0, 'category_id': u'Frontend', 'number': 24, 'id': 134}, 25: {'category': u'Frontend', 'comment': u'Low-Power, 8-Channel, 24-Bit Analog Front-End for Biopotential Measurements', 'farnell': '', 'name': u'ADS1298', 'datasheet': u'http://www.ti.com/lit/ds/symlink/ads1296r.pdf', 'quantity': 0, 'category_id': u'Frontend', 'number': 25, 'id': 133}, 26: {'category': u'ADC', 'comment': u'Low-Power, 16-Bit, 500kSPS, 4-/8-Channel Unipolar Input ANALOG-TO-DIGITAL CONVERTERS with Serial Interface', 'farnell': '', 'name': u'ADS8331', 'datasheet': u'http://www.ti.com/lit/ds/symlink/ads8332.pdf', 'quantity': 15, 'category_id': u'ADC', 'number': 26, 'id': 75}, 27: {'category': u'ADC', 'comment': u'Low-Power, 16-Bit, 500kSPS, 4-/8-Channel Unipolar Input ANALOG-TO-DIGITAL CONVERTERS with Serial Interface', 'farnell': '', 'name': u'ADS8331', 'datasheet': u'http://www.ti.com/lit/ds/symlink/ads8332.pdf', 'quantity': 5, 'category_id': u'ADC', 'number': 27, 'id': 74}, 28: {'category': u'Microcontroller', 'comment': u'Cortex M3, Analog Devices', 'farnell': '', 'name': u'ADUCM360', 'datasheet': u'http://www.analog.com/media/en/technical-documentation/evaluation-documentation/UG-367.pdf?doc=AN-1320.pdf', 'quantity': 4, 'category_id': u'Microcontroller', 'number': 28, 'id': 27}, 29: {'category': u'Microcontroller', 'comment': u'Cortex M3, Analog Devices', 'farnell': '', 'name': u'ADUCM361', 'datasheet': u'http://www.analog.com/media/en/technical-documentation/evaluation-documentation/UG-367.pdf?doc=AN-1320.pdf', 'quantity': 4, 'category_id': u'Microcontroller', 'number': 29, 'id': 28}, 30: {'category': u'Trafo/Isolator', 'comment': u'1Mhz, 3.75 kV, 7-Channel, SPIsolator Digital Isolators for SPI', 'farnell': u'2451484', 'name': u'ADUM3152ARSZ', 'datasheet': u'http://www.analog.com/media/en/technical-documentation/data-sheets/ADuM3151_3152_3153.pdf', 'quantity': 4, 'category_id': u'Trafo/Isolator', 'number': 30, 'id': 29}, 31: {'category': u'Trafo/Isolator', 'comment': u'17Mhz, 3.75 kV, 7-Channel, SPIsolator Digital Isolators for SPI', 'farnell': '', 'name': u'ADUM3152BRSZ', 'datasheet': u'http://www.analog.com/media/en/technical-documentation/data-sheets/ADuM3151_3152_3153.pdf', 'quantity': 9, 'category_id': u'Trafo/Isolator', 'number': 31, 'id': 126}, 32: {'category': u'Connector', 'comment': u'pcb slim Smart card connector', 'farnell': u'1849555', 'name': u'AMPHENOL TUCHEL C702 10M008 252 40', 'datasheet': '', 'quantity': 2, 'category_id': u'Connector', 'number': 32, 'id': 115}, 33: {'category': u'Memory', 'comment': u'64Mb / 4M x 16 bit Synchronous DRAM (SDRAM)', 'farnell': '', 'name': u'AS4C4M16S', 'datasheet': u'http://www.alliancememory.com/pdf/dram/64m-as4c4m16s.pdf', 'quantity': 2, 'category_id': u'Memory', 'number': 33, 'id': 147}, 34: {'category': u'Diode', 'comment': u'Schottky barrier diodes', 'farnell': u'9801502', 'name': u'BAT54', 'datasheet': u'http://www.nxp.com/documents/data_sheet/BAT54_SER.pdf', 'quantity': 23, 'category_id': u'Diode', 'number': 34, 'id': 41}, 35: {'category': u'PNP transistor', 'comment': u'45 V, 500 mA PNP general-purpose transistors', 'farnell': '', 'name': u'BC807', 'datasheet': u'http://www.nxp.com/documents/data_sheet/BC807_BC807W_BC327.pdf', 'quantity': 11, 'category_id': u'PNP transistor', 'number': 35, 'id': 2}, 36: {'category': u'NPN transistor', 'comment': u'45 V, 1 A NPN medium power transistors', 'farnell': '', 'name': u'BCP54', 'datasheet': u'http://www.nxp.com/documents/data_sheet/BCP54_BCX54_BC54PA.pdf', 'quantity': 37, 'category_id': u'NPN transistor', 'number': 36, 'id': 4}, 37: {'category': u'Inductor/Ferrite', 'comment': u'Ferrite bead, 0.21 Ohm,  500mA, 0603', 'farnell': u'1515722', 'name': u'BLM18EG471SN1D', 'datasheet': u'http://www.farnell.com/datasheets/5169.pdf', 'quantity': 118, 'category_id': u'Inductor/Ferrite', 'number': 37, 'id': 49}, 38: {'category': u'NPN transistor', 'comment': u'150 V, 1 A NPN high-voltage low VCEsat (BISS) transistor', 'farnell': '', 'name': u'BPHV8115Z', 'datasheet': u'http://www.nxp.com/documents/data_sheet/PBHV8115Z.pdf', 'quantity': 10, 'category_id': u'NPN transistor', 'number': 38, 'id': 3}, 39: {'category': u'Battery', 'comment': u'Cost-Effective Voltage and Current Protection Integrated Circuit for Single-Cell Li-Ion/Li-Polymer Batteries', 'farnell': u'2425793', 'name': u'BQ29700DSET', 'datasheet': u'http://www.ti.com/lit/ds/symlink/bq29700.pdf', 'quantity': 2, 'category_id': u'Battery', 'number': 39, 'id': 160}, 40: {'category': u'NPN transistor', 'comment': u'80 V, 1 A NPN Darlington transistors', 'farnell': '', 'name': u'BSP51', 'datasheet': u'http://www.nxp.com/documents/data_sheet/BSP50_51_52.pdf', 'quantity': 23, 'category_id': u'NPN transistor', 'number': 40, 'id': 5}, 41: {'category': u'NMOS transistor', 'comment': u'55 V, 5.5 A, N-channel TrenchMOS logic level FET', 'farnell': u'1769679', 'name': u'BUK98150-55A', 'datasheet': u'http://www.nxp.com/documents/data_sheet/BUK98150-55A.pdf', 'quantity': 11, 'category_id': u'NMOS transistor', 'number': 41, 'id': 131}, 42: {'category': u'IC', 'comment': u'Bluetooth\xae CC2560 Controller', 'farnell': '', 'name': u'CC2560A', 'datasheet': u'http://www.ti.com/lit/ds/symlink/cc2560.pdf', 'quantity': 2, 'category_id': u'IC', 'number': 42, 'id': 46}, 43: {'category': u'IC', 'comment': u'Dual-Mode Bluetooth\xae CC2564 Controller', 'farnell': u'2377525', 'name': u'CC2564B', 'datasheet': u'http://www.ti.com/lit/ds/symlink/cc2564.pdf', 'quantity': 0, 'category_id': u'IC', 'number': 43, 'id': 45}, 44: {'category': u'IC', 'comment': u'High-Speed CMOS Logic Dual 4-Input NAND Gate', 'farnell': u'1739953', 'name': u'CD74HCT20M', 'datasheet': u'http://www.ti.com/lit/ds/symlink/cd54hc20.pdf', 'quantity': 0, 'category_id': u'IC', 'number': 44, 'id': 124}, 45: {'category': u'Oscillator/Crystal', 'comment': u'12Mhz', 'farnell': u'1841963', 'name': u'CRYSTAL 12MHZ', 'datasheet': '', 'quantity': 8, 'category_id': u'Oscillator/Crystal', 'number': 45, 'id': 11}, 46: {'category': u'Oscillator/Crystal', 'comment': u'16Mhz - 18pf - zapestnica', 'farnell': '', 'name': u'CRYSTAL 16MHZ', 'datasheet': '', 'quantity': 7, 'category_id': u'Oscillator/Crystal', 'number': 46, 'id': 62}, 47: {'category': u'Oscillator/Crystal', 'comment': u'26Mhz', 'farnell': u'1842069', 'name': u'CRYSTAL 26MHZ', 'datasheet': '', 'quantity': 1, 'category_id': u'Oscillator/Crystal', 'number': 47, 'id': 13}, 48: {'category': u'Oscillator/Crystal', 'comment': u'27.17Mhz', 'farnell': u'1842001', 'name': u'CRYSTAL 27.17MHZ', 'datasheet': '', 'quantity': 6, 'category_id': u'Oscillator/Crystal', 'number': 48, 'id': 12}, 49: {'category': u'Oscillator/Crystal', 'comment': u'8Mhz', 'farnell': u'2308726', 'name': u'CRYSTAL 8MHZ', 'datasheet': '', 'quantity': 8, 'category_id': u'Oscillator/Crystal', 'number': 49, 'id': 10}, 50: {'category': u'IC', 'comment': u'MAX232x Dual EIA-232 Drivers/Receivers', 'farnell': u'1648736', 'name': u'EIA232', 'datasheet': u'http://www.farnell.com/datasheets/1900600.pdf', 'quantity': 3, 'category_id': u'IC', 'number': 50, 'id': 157}, 51: {'category': u'Inductor/Ferrite', 'comment': u'inductor za ltc4001,', 'farnell': u'1888678', 'name': u'ELL5PR1R5N', 'datasheet': u'http://www.farnell.com/datasheets/1303736.pdf', 'quantity': 2, 'category_id': u'Inductor/Ferrite', 'number': 51, 'id': 15}, 52: {'category': u'Fuse', 'comment': u'FUSE, SMD 0402, 2A', 'farnell': u'1897009', 'name': u'ERBRD2R00X', 'datasheet': u'http://www.farnell.com/datasheets/1328695.pdf', 'quantity': 20, 'category_id': u'Fuse', 'number': 52, 'id': 70}, 53: {'category': u'Diode', 'comment': u'Rectifier Diode, Single, 50 V, 1 A, 920 mV, 35 ns, 30 A', 'farnell': u'1625028', 'name': u'ES1A', 'datasheet': u'http://www.farnell.com/datasheets/1662095.pdf', 'quantity': 16, 'category_id': u'Diode', 'number': 53, 'id': 37}, 54: {'category': u'Other', 'comment': u'Light Touch Switches/EVQP0/Q2', 'farnell': u'1821256', 'name': u'EVQQ2W02W', 'datasheet': u'http://www.farnell.com/datasheets/578646.pdf', 'quantity': 20, 'category_id': u'Other', 'number': 54, 'id': 150}, 55: {'category': u'PMOS transistor', 'comment': u'35V, 4.3A, 55m\u2126, P-Channel PowerTrench\xae MOSFET', 'farnell': u'2322580', 'name': u'FDC365P', 'datasheet': u'http://www.farnell.com/datasheets/1707071.pdf', 'quantity': 10, 'category_id': u'PMOS transistor', 'number': 55, 'id': 130}, 56: {'category': u'PMOS transistor', 'comment': u'30 V, 4A, Single P-Channel, Logic Level, PowerTrenchTM MOSFET', 'farnell': u'9846441', 'name': u'FDC658P', 'datasheet': u'http://www.farnell.com/datasheets/237327.pdf', 'quantity': 14, 'category_id': u'PMOS transistor', 'number': 56, 'id': 92}, 57: {'category': u'PMOS transistor', 'comment': u'20 V, 5 A, Single P-Channel 2.5V Specified MOSFET', 'farnell': u'9846247', 'name': u'FDS8433A', 'datasheet': u'https://www.fairchildsemi.com/datasheets/FD/FDS8433A.pdf', 'quantity': 4, 'category_id': u'PMOS transistor', 'number': 57, 'id': 32}, 58: {'category': u'Other', 'comment': u'cc2564 - 2.54ghz', 'farnell': '', 'name': u'FILTER 2.54GHZ', 'datasheet': '', 'quantity': 17, 'category_id': u'Other', 'number': 58, 'id': 69}, 59: {'category': u'IC', 'comment': u'Dual High Speed USB to Multipurpose UART/FIFO IC', 'farnell': u'1615843', 'name': u'FT2232', 'datasheet': u'http://www.ftdichip.com/Support/Documents/DataSheets/ICs/DS_FT2232H.pdf', 'quantity': 1, 'category_id': u'IC', 'number': 59, 'id': 57}, 60: {'category': u'IC', 'comment': u'USB to BASIC UART IC', 'farnell': u'2081324', 'name': u'FT230X', 'datasheet': u'http://www.ftdichip.com/Support/Documents/DataSheets/ICs/DS_FT230X.pdf', 'quantity': 6, 'category_id': u'IC', 'number': 60, 'id': 56}, 61: {'category': u'OP AMP', 'comment': u'microPower, Single-Supply, CMOS, Instrumentation Amplifier', 'farnell': u'1206912', 'name': u'INA321EA', 'datasheet': u'http://www.ti.com/lit/ds/sbos168d/sbos168d.pdf', 'quantity': 2, 'category_id': u'OP AMP', 'number': 61, 'id': 84}, 62: {'category': u'OP AMP', 'comment': u'Low-Power, Single-Supply, CMOS\n, INSTRUMENTATION AMPLIFIERS', 'farnell': u'1645385', 'name': u'INA332', 'datasheet': u'http://www.ti.com/lit/ds/sbos216b/sbos216b.pdf', 'quantity': 3, 'category_id': u'OP AMP', 'number': 62, 'id': 87}, 63: {'category': u'NMOS transistor', 'comment': u'N-channel 200V - 0.35\u2126 - 9A TO-220/TO-220FP Mesh overlay\u2122 II Power MOSFET', 'farnell': u'9802380', 'name': u'IRF630', 'datasheet': u'http://www.st.com/web/en/resource/technical/document/datasheet/CD00000701.pdf', 'quantity': 2, 'category_id': u'NMOS transistor', 'number': 63, 'id': 129}, 64: {'category': u'NMOS transistor', 'comment': u'Dual N Channel, 4.8 A, 20 V, 35 mohm, 4.5 V, 1.2 V', 'farnell': u'1298558', 'name': u'IRF7757TRPBF', 'datasheet': u'http://www.farnell.com/datasheets/55505.pdf', 'quantity': 12, 'category_id': u'NMOS transistor', 'number': 64, 'id': 159}, 65: {'category': u'PMOS transistor', 'comment': u'30 V, 12 A, Single P-Channel', 'farnell': u'1831076', 'name': u'IRF9328PBF', 'datasheet': u'http://www.irf.com/product-info/datasheets/data/irf9328pbf.pdf', 'quantity': 6, 'category_id': u'PMOS transistor', 'number': 65, 'id': 33}, 66: {'category': u'Memory', 'comment': u'1 Meg Bits x 16 Bits x 4 Banks (64-MBIT) SYNCHRONOUS DYNAMIC RAM', 'farnell': u'2253831', 'name': u'IS42S16400', 'datasheet': u'http://www.issi.com/WW/pdf/42S16400.pdf', 'quantity': 1, 'category_id': u'Memory', 'number': 66, 'id': 59}, 67: {'category': u'Other', 'comment': u'C & K Components KMR442GULCLFS  Switch, Ground, 4N, Low current', 'farnell': u'1908256', 'name': u'KMR442GULCLFS', 'datasheet': u'http://www.farnell.com/datasheets/1443639.pdf', 'quantity': 8, 'category_id': u'Other', 'number': 67, 'id': 165}, 68: {'category': u'LDO', 'comment': u'3.3 V, Adjustable and fixed low drop positive voltage regulator', 'farnell': u'1202826', 'name': u'LD1117S33TR', 'datasheet': u'http://www.st.com/web/en/resource/technical/document/datasheet/CD00000544.pdf', 'quantity': 4, 'category_id': u'LDO', 'number': 68, 'id': 97}, 69: {'category': u'LDO', 'comment': u'1.3 V, Ultra low dropout regulators, low noise, 300 mA', 'farnell': u'2308309', 'name': u'LD6836TD/13H', 'datasheet': u'http://www.farnell.com/datasheets/1697991.pdf', 'quantity': 10, 'category_id': u'LDO', 'number': 69, 'id': 107}, 70: {'category': u'LDO', 'comment': u'3.3 V, Ultra low dropout regulators, low noise, 300 mA', 'farnell': u'2308336', 'name': u'LD6836TD/33H', 'datasheet': u'http://www.farnell.com/datasheets/1697991.pdf', 'quantity': 6, 'category_id': u'LDO', 'number': 70, 'id': 108}, 71: {'category': u'LDO', 'comment': u'3.6 V Ultra low dropout regulators, low noise, 300 mA', 'farnell': u'2308338', 'name': u'LD6836TD/36H', 'datasheet': u'http://www.farnell.com/datasheets/1697991.pdf', 'quantity': 4, 'category_id': u'LDO', 'number': 71, 'id': 109}, 72: {'category': u'LDO', 'comment': u'5 V, 800mA Low-Dropout Linear Regulator', 'farnell': u'9778209', 'name': u'LM1117-50', 'datasheet': u'http://www.ti.com/lit/ds/symlink/lm1117-n.pdf', 'quantity': 3, 'category_id': u'LDO', 'number': 72, 'id': 99}, 73: {'category': u'OP AMP', 'comment': u'op amp', 'farnell': '', 'name': u'LM131-2616', 'datasheet': '', 'quantity': 7, 'category_id': u'OP AMP', 'number': 73, 'id': 83}, 74: {'category': u'IC', 'comment': u'QUAD DIFFERENTIAL COMPARATORS', 'farnell': u'2342312', 'name': u'LM239DR', 'datasheet': u'http://datasheet.octopart.com/LM239DR-Texas-Instruments-datasheet-10481377.pdf', 'quantity': 4, 'category_id': u'IC', 'number': 74, 'id': 125}, 75: {'category': u'Boost Switcher', 'comment': u'LM27313/-Q1 1.6-MHz Boost Converter With 30-V Internal FET Switch in SOT-23\n', 'farnell': u'1564771', 'name': u'LM27313', 'datasheet': u'http://www.ti.com/lit/ds/symlink/lm27313-q1.pdf', 'quantity': 9, 'category_id': u'Boost Switcher', 'number': 75, 'id': 16}, 76: {'category': u'Boost Switcher', 'comment': u' High-Efficiency Low-Side N-Channel Controller for Switching Regulator', 'farnell': u'1312568', 'name': u'LM3478MM', 'datasheet': u'http://www.ti.com/lit/ds/symlink/lm3478.pdf', 'quantity': 0, 'category_id': u'Boost Switcher', 'number': 76, 'id': 73}, 77: {'category': u'Buck Switcher', 'comment': u'1.8 V, LM3671/-Q1 2-MHz, 600-mA Step-Down DC-DC Converter', 'farnell': u'1685654', 'name': u'LM3671MF-1.8', 'datasheet': u'http://www.farnell.com/datasheets/1882649.pdf', 'quantity': 5, 'category_id': u'Buck Switcher', 'number': 77, 'id': 18}, 78: {'category': u'Buck Switcher', 'comment': u'3.3 V, LM3671/-Q1 2-MHz, 600-mA Step-Down DC-DC Converter', 'farnell': u'1685767', 'name': u'LM3671MF-3.3', 'datasheet': u'http://www.farnell.com/datasheets/1882649.pdf', 'quantity': 5, 'category_id': u'Buck Switcher', 'number': 78, 'id': 17}, 79: {'category': u'Buck Switcher', 'comment': u'5.5Vin, 2.0A Step-Down Voltage Regulator in WSON', 'farnell': u'2064689', 'name': u'LMR10520YSD', 'datasheet': u'http://www.ti.com/lit/ds/symlink/lmr10520.pdf', 'quantity': 4, 'category_id': u'Buck Switcher', 'number': 79, 'id': 119}, 80: {'category': u'MEMS', 'comment': u'iNEMO inertial module: always-on 3D accelerometer and 3D gyroscope', 'farnell': u'2474253', 'name': u'LSM6DS3', 'datasheet': u'http://www.st.com/web/en/resource/technical/document/datasheet/DM00133076.pdf', 'quantity': 2, 'category_id': u'MEMS', 'number': 80, 'id': 158}, 81: {'category': u'Trafo/Isolator', 'comment': u'7Slew Rate Controlled Ultralow Noise1A Isolated DC/DC Transformer Driver', 'farnell': u'1273815', 'name': u'LT3439', 'datasheet': u'http://cds.linear.com/docs/en/datasheet/3439fs.pdf', 'quantity': 0, 'category_id': u'Trafo/Isolator', 'number': 81, 'id': 53}, 82: {'category': u'Battery', 'comment': u'2A Synchronous Buck Li-Ion Charger', 'farnell': u'1556266', 'name': u'LTC4001', 'datasheet': u'http://cds.linear.com/docs/en/datasheet/40011fa.pdf', 'quantity': 1, 'category_id': u'Battery', 'number': 82, 'id': 14}, 83: {'category': u'Trafo/Isolator', 'comment': u'1A, Spread-Spectrum, Push-Pull, Transformer Driver for Isolated Power Supplies', 'farnell': '', 'name': u'MAX13253', 'datasheet': u'https://datasheets.maximintegrated.com/en/ds/MAX13253.pdf', 'quantity': 4, 'category_id': u'Trafo/Isolator', 'number': 83, 'id': 47}, 84: {'category': u'Trafo/Isolator', 'comment': u'Transformer Driver for\n Isolated RS-485 Interface', 'farnell': u'1379887', 'name': u'MAX253ESA', 'datasheet': u'http://datasheets.maximintegrated.com/en/ds/MAX253.pdf', 'quantity': 1, 'category_id': u'Trafo/Isolator', 'number': 84, 'id': 68}, 85: {'category': u'Fuse', 'comment': u'PTC RESET, SMD, 6V, 1.25A', 'farnell': u'1861168', 'name': u'MC36226', 'datasheet': u'http://www.farnell.com/datasheets/1864080.pdf', 'quantity': 16, 'category_id': u'Fuse', 'number': 85, 'id': 142}, 86: {'category': u'Inductor/Ferrite', 'comment': u'4.7 \xb5H, \xb1 10%, 1210 [3225 Metric], 50 MHz', 'farnell': u'1864317', 'name': u'MCNL322522B2-4R7K', 'datasheet': u'http://www.farnell.com/datasheets/1328465.pdf', 'quantity': 9, 'category_id': u'Inductor/Ferrite', 'number': 86, 'id': 156}, 87: {'category': u'Boost Switcher', 'comment': u'0.65V Start-up Synchronous Boost Regulator with True Output Disconnect or Input/Output Bypass Option', 'farnell': u'1800207', 'name': u'MCP1640', 'datasheet': u'http://ww1.microchip.com/downloads/en/DeviceDoc/22234B.pdf', 'quantity': 18, 'category_id': u'Boost Switcher', 'number': 87, 'id': 43}, 88: {'category': u'LDO', 'comment': u'2.5 V, 300 mA, Low Voltage, Low Quiescent Current LDO Regulator', 'farnell': '', 'name': u'MCP1824S25', 'datasheet': u'http://ww1.microchip.com/downloads/en/DeviceDoc/22070a.pdf', 'quantity': 8, 'category_id': u'LDO', 'number': 88, 'id': 9}, 89: {'category': u'LDO', 'comment': u'3.3 V, 300 mA, Low Voltage, Low Quiescent Current LDO Regulator', 'farnell': '', 'name': u'MCP1824S33', 'datasheet': u'http://ww1.microchip.com/downloads/en/DeviceDoc/22070a.pdf', 'quantity': 12, 'category_id': u'LDO', 'number': 89, 'id': 8}, 90: {'category': u'OP AMP', 'comment': u'4 MHz, Low-Input Bias Current Op Amps', 'farnell': u'2348039', 'name': u'MCP6484-E/SL', 'datasheet': u'http://ww1.microchip.com/downloads/en/DeviceDoc/20002322C.pdf', 'quantity': 3, 'category_id': u'OP AMP', 'number': 90, 'id': 128}, 91: {'category': u'OP AMP', 'comment': u'4 MHz, Low-Input Bias Current Op Amps', 'farnell': u'2348040', 'name': u'MCP6484-E/SL', 'datasheet': u'http://ww1.microchip.com/downloads/en/DeviceDoc/20002322C.pdf', 'quantity': 1, 'category_id': u'OP AMP', 'number': 91, 'id': 146}, 92: {'category': u'Battery', 'comment': u'Simple, Miniature Single-Cell, Fully Integrated Li-Ion / Li-Polymer Charge Management Controllers', 'farnell': u'1439477', 'name': u'MCP73811T', 'datasheet': u'http://ww1.microchip.com/downloads/en/DeviceDoc/22036a.pdf', 'quantity': 2, 'category_id': u'Battery', 'number': 92, 'id': 51}, 93: {'category': u'Battery', 'comment': u'Simple, Miniature Single-Cell, Fully Integrated Li-Ion / Li-Polymer Charge Management Controllers', 'farnell': u'1627187', 'name': u'MCP73812T', 'datasheet': u'http://ww1.microchip.com/downloads/en/DeviceDoc/22036a.pdf', 'quantity': 11, 'category_id': u'Battery', 'number': 93, 'id': 44}, 94: {'category': u'Battery', 'comment': u'Stand-Alone Linear Li-Ion / Li-Polymer Charge Management Controller', 'farnell': '', 'name': u'MCP73833', 'datasheet': u'http://ww1.microchip.com/downloads/en/DeviceDoc/22005a.pdf', 'quantity': 16, 'category_id': u'Battery', 'number': 94, 'id': 91}, 95: {'category': u'IC', 'comment': u'Standard 3V ISO/IEC 14443 A/B reader solution - RFID', 'farnell': u'1902839', 'name': u'MFRC52302', 'datasheet': u'http://www.nxp.com/documents/data_sheet/MFRC523.pdf', 'quantity': 5, 'category_id': u'IC', 'number': 95, 'id': 100}, 96: {'category': u'MEMS', 'comment': u'MMA8453Q, 3-Axis, 10-bit/8-bit Digital Accelerometer', 'farnell': u'2238136', 'name': u'MMA8453', 'datasheet': u'http://www.farnell.com/datasheets/1934980.pdf', 'quantity': 10, 'category_id': u'MEMS', 'number': 96, 'id': 61}, 97: {'category': u'MEMS', 'comment': u'MMA8652FC, 3-Axis, 12-bit, Digital Accelerometer', 'farnell': u'2377758', 'name': u'MMA8652FCR', 'datasheet': u'http://cache.freescale.com/files/sensors/doc/data_sheet/MMA8652FC.pdf', 'quantity': 1, 'category_id': u'MEMS', 'number': 97, 'id': 71}, 98: {'category': u'MEMS', 'comment': u'MPL3115A2, I2C Precision Altimeter', 'farnell': '', 'name': u'MPL3115', 'datasheet': u'http://cache.freescale.com/files/sensors/doc/data_sheet/MPL3115A2.pdf', 'quantity': 3, 'category_id': u'MEMS', 'number': 98, 'id': 66}, 99: {'category': u'Microcontroller', 'comment': u'MIXED SIGNAL MICROCONTROLLER', 'farnell': '', 'name': u'MSP430F169', 'datasheet': u'http://www.ti.com/lit/ds/symlink/msp430f169.pdf', 'quantity': 3, 'category_id': u'Microcontroller', 'number': 99, 'id': 63}, 100: {'category': u'Inductor/Ferrite', 'comment': u'3.3 \xb5H, \xb1 30%, Shielded, 0.014 ohm, 7 A', 'farnell': u'2288442', 'name': u'MSS1260-332NLD', 'datasheet': u'http://www.farnell.com/datasheets/1681951.pdf', 'quantity': 5, 'category_id': u'Inductor/Ferrite', 'number': 100, 'id': 103}, 101: {'category': u'Connector', 'comment': u'Dsub 15 pin pcb connector', 'farnell': u'1848373', 'name': u'MULTICOMP \t5504F1-15S-02A-03 ', 'datasheet': '', 'quantity': 11, 'category_id': u'Connector', 'number': 101, 'id': 118}, 102: {'category': u'Microcontroller', 'comment': u'Cortex M0, Bluetooth Smart and 2.4GHz proprietary SoC', 'farnell': '', 'name': u'NRF51822', 'datasheet': '', 'quantity': 11, 'category_id': u'Microcontroller', 'number': 102, 'id': 58}, 103: {'category': u'PMOS transistor', 'comment': u'30 V, 230 mA P-channel Trench MOSFET', 'farnell': u'2069547', 'name': u'NX3008PBK', 'datasheet': u'http://www.nxp.com/documents/data_sheet/NX3008PBK.pdf', 'quantity': 60, 'category_id': u'PMOS transistor', 'number': 103, 'id': 6}, 104: {'category': u'OP AMP', 'comment': u'1.8-V MICROPOWER CMOS OPERATIONAL AMPLIFIERS ZERO-DRIFT SERIES', 'farnell': '', 'name': u'OPA2333A', 'datasheet': u'http://www.ti.com/lit/ds/symlink/opa333a-ep.pdf', 'quantity': 3, 'category_id': u'OP AMP', 'number': 104, 'id': 55}, 105: {'category': u'OP AMP', 'comment': u'Low-Power, Low-Noise, RRIO, 1.8-V CMOS Operational Amplifier', 'farnell': u'2323362', 'name': u'OPA314AIDBVT', 'datasheet': u'http://www.ti.com/lit/ds/symlink/opa4314.pdf', 'quantity': 1, 'category_id': u'OP AMP', 'number': 105, 'id': 106}, 106: {'category': u'Optical', 'comment': u'warm white led', 'farnell': u'2078796', 'name': u'OSRAM LED', 'datasheet': u'http://www.farnell.com/datasheets/1514325.pdf', 'quantity': 20, 'category_id': u'Optical', 'number': 106, 'id': 72}, 107: {'category': u'Optical', 'comment': u'Full-Color 1204 SMD (150\xb0 Viewing Angle)', 'farnell': u'1678707', 'name': u'OVSRRGBCC3', 'datasheet': u'http://optekinc.com/datasheets/ovsrrgbcc3.pdf', 'quantity': 8, 'category_id': u'Optical', 'number': 107, 'id': 166}, 108: {'category': u'Module', 'comment': u'Bluetooth-SPP Module', 'farnell': u'2325988', 'name': u'PAN1322', 'datasheet': '', 'quantity': 0, 'category_id': u'Module', 'number': 108, 'id': 94}, 109: {'category': u'Diode', 'comment': u'ESD protection diodes in SOD523 package', 'farnell': u'8737711', 'name': u'PESD5V0S1UB', 'datasheet': u'http://www.nxp.com/documents/data_sheet/PESDXS1UB_SERIES.pdf', 'quantity': 3, 'category_id': u'Diode', 'number': 109, 'id': 144}, 110: {'category': u'Diode', 'comment': u'5V, Double ESD protection diodes in SOT663 package', 'farnell': u'8737746', 'name': u'PESD5V0S2UQ', 'datasheet': u'http://www.nxp.com/documents/data_sheet/PESDXS2UQ_SER.pdf', 'quantity': 7, 'category_id': u'Diode', 'number': 110, 'id': 123}, 111: {'category': u'Inductor/Ferrite', 'comment': u'4.7 \xb5H, \xb1 20%, Shielded, 0.34 ohm, 770 mA', 'farnell': u'2288748', 'name': u'PFL2015-472MEC ', 'datasheet': u'http://www.farnell.com/datasheets/1681966.pdf', 'quantity': 10, 'category_id': u'Inductor/Ferrite', 'number': 111, 'id': 155}, 112: {'category': u'Inductor/Ferrite', 'comment': u'2.2 uH, Shielded Power Inductor', 'farnell': u'2288754', 'name': u'PFL2510-222MEB ', 'datasheet': u'http://www.farnell.com/datasheets/1681967.pdf', 'quantity': 1, 'category_id': u'Inductor/Ferrite', 'number': 112, 'id': 19}, 113: {'category': u'Diode', 'comment': u'10 V, 2 A ultra low VF MEGA Schottky barrier rectifiers', 'farnell': u'8737835', 'name': u'PMEG1020', 'datasheet': u'http://www.nxp.com/documents/data_sheet/PMEG1020EH_EJ.pdf', 'quantity': 21, 'category_id': u'Diode', 'number': 113, 'id': 40}, 114: {'category': u'Diode', 'comment': u'0.5 A very low VF MEGA Schottky barrier rectifiers', 'farnell': u'1510671', 'name': u'PMEG2005EH', 'datasheet': u'http://www.nxp.com/documents/data_sheet/PMEGXX05EH_EJ_SER.pdf', 'quantity': 13, 'category_id': u'Diode', 'number': 114, 'id': 104}, 115: {'category': u'Diode', 'comment': u'20V 1.5A Schottky barrier\n diode', 'farnell': u'8737924', 'name': u'PMEG2015EA', 'datasheet': u'http://www.nxp.com/documents/data_sheet/PMEG2015EA.pdf', 'quantity': 16, 'category_id': u'Diode', 'number': 115, 'id': 117}, 116: {'category': u'Diode', 'comment': u'5 A low VF MEGA Schottky barrier rectifier', 'farnell': '', 'name': u'PMEG3050', 'datasheet': u'http://www.nxp.com/documents/data_sheet/PMEG3050EP.pdf', 'quantity': 18, 'category_id': u'Diode', 'number': 116, 'id': 38}, 117: {'category': u'Diode', 'comment': u'12V, 400 W Transient Voltage Suppressor', 'farnell': u'1829237', 'name': u'PTVS12VS1UR', 'datasheet': u'http://www.nxp.com/documents/data_sheet/PTVSXS1UR_SER.pdf', 'quantity': 7, 'category_id': u'Diode', 'number': 117, 'id': 121}, 118: {'category': u'Diode', 'comment': u'5V, 400 W Transient Voltage Suppressor', 'farnell': u'1829262', 'name': u'PTVS5V0S1UR', 'datasheet': u'http://www.nxp.com/documents/data_sheet/PTVSXS1UR_SER.pdf', 'quantity': 3, 'category_id': u'Diode', 'number': 118, 'id': 120}, 119: {'category': u'Diode', 'comment': u'6V, 400 W Transient Voltage Suppressor', 'farnell': u'1829265', 'name': u'PTVS6V0S1UR', 'datasheet': u'http://www.nxp.com/documents/data_sheet/PTVSXS1UR_SER.pdf', 'quantity': 8, 'category_id': u'Diode', 'number': 119, 'id': 122}, 120: {'category': u'Diode', 'comment': u'30 V, 200 mA, Schottky Barrier Diode', 'farnell': u'2317433', 'name': u'RB521S30T16', 'datasheet': u'http://www.farnell.com/datasheets/1708344.pdf', 'quantity': 2, 'category_id': u'Diode', 'number': 120, 'id': 101}, 121: {'category': u'Memory', 'comment': u'64 Mbit (8 Mbyte) Flash', 'farnell': '', 'name': u'S25FL164K0', 'datasheet': u'http://www.farnell.com/datasheets/1756776.pdf', 'quantity': 3, 'category_id': u'Memory', 'number': 121, 'id': 60}, 122: {'category': u'Oscillator/Crystal', 'comment': u'32kHz osc low power', 'farnell': u'2405744', 'name': u'SG-3030CM', 'datasheet': u'http://www.epsondevice.com/docs/qd/en/DownloadServlet?id=ID000648', 'quantity': 1, 'category_id': u'Oscillator/Crystal', 'number': 122, 'id': 111}, 123: {'category': u'Oscillator/Crystal', 'comment': u'32kHz osc low power', 'farnell': u'1278042', 'name': u'SG-3030JF', 'datasheet': u'http://www.epsondevice.com/docs/qd/en/DownloadServlet?id=ID000648', 'quantity': 2, 'category_id': u'Oscillator/Crystal', 'number': 123, 'id': 110}, 124: {'category': u'Diode', 'comment': u'Transient Voltage Suppression Diodes, 400W', 'farnell': u'1886343', 'name': u'SMAJ15A', 'datasheet': u'http://www.farnell.com/datasheets/607617.pdf', 'quantity': 11, 'category_id': u'Diode', 'number': 124, 'id': 112}, 125: {'category': u'Diode', 'comment': u'Transient Voltage Suppression Diodes 1500W', 'farnell': u'1886367', 'name': u'SMCJ50A', 'datasheet': u'http://www.farnell.com/datasheets/607636.pdf', 'quantity': 20, 'category_id': u'Diode', 'number': 125, 'id': 35}, 126: {'category': u'Diode', 'comment': u' USB port transient suppressor diode', 'farnell': u'8451990', 'name': u'SN65220', 'datasheet': u'http://www.ti.com/lit/ds/symlink/sn75240.pdf', 'quantity': 6, 'category_id': u'Diode', 'number': 126, 'id': 145}, 127: {'category': u'Diode', 'comment': u'SCR Diode Array for ESD and Transient Overvoltage Protection', 'farnell': u'1785542', 'name': u'SP724AHTG', 'datasheet': u'http://www.farnell.com/datasheets/47292.pdf', 'quantity': 17, 'category_id': u'Diode', 'number': 127, 'id': 36}, 128: {'category': u'PMOS transistor', 'comment': u'30 V, 10 A, Automotive P-Channel 30 V (D-S) 175 \xb0C MOSFET', 'farnell': u'1869893', 'name': u'SQ4431EY', 'datasheet': u'http://www.vishay.com/docs/65527/sq4431ey.pdf', 'quantity': 10, 'category_id': u'PMOS transistor', 'number': 128, 'id': 34}, 129: {'category': u'IC', 'comment': u'16-pin smartcard interfaces IC', 'farnell': u'2462678', 'name': u'ST8034P', 'datasheet': u'http://www.st.com/web/en/resource/technical/document/datasheet/DM00083175.pdf', 'quantity': 1, 'category_id': u'IC', 'number': 129, 'id': 116}, 130: {'category': u'NMOS transistor', 'comment': u'N-channel 30 V, 0.0042 \u2126 , 80 A, DPAK, TO-220, IPAK, STripFET\u2122 V Power MOSFET', 'farnell': u'1752057', 'name': u'STD85N3LH5', 'datasheet': u'http://www.farnell.com/datasheets/1723976.pdf', 'quantity': 11, 'category_id': u'NMOS transistor', 'number': 130, 'id': 90}, 131: {'category': u'Microcontroller', 'comment': u'Cortex M0, 48Mhz, 32KB Flash, 16KB RAM ', 'farnell': u'2432085', 'name': u'STM32F030K6', 'datasheet': '', 'quantity': 2, 'category_id': u'Microcontroller', 'number': 131, 'id': 154}, 132: {'category': u'Microcontroller', 'comment': u'Cortex M3, 24Mhz, 16KB Flash, 8KB RAM ', 'farnell': u'1838504', 'name': u'STM32F100C4', 'datasheet': '', 'quantity': 7, 'category_id': u'Microcontroller', 'number': 132, 'id': 137}, 133: {'category': u'Microcontroller', 'comment': u'Cortex M3, 24Mhz, 64KB Flash, 8KB RAM ', 'farnell': u'1838511', 'name': u'STM32F100C8', 'datasheet': '', 'quantity': 1, 'category_id': u'Microcontroller', 'number': 133, 'id': 136}, 134: {'category': u'Microcontroller', 'comment': u'Cortex M3, 72Mhz, 64KB Flash, 20KB RAM', 'farnell': u'1447637', 'name': u'STM32F103C8', 'datasheet': '', 'quantity': 9, 'category_id': u'Microcontroller', 'number': 134, 'id': 135}, 135: {'category': u'Microcontroller', 'comment': u'Cortex M3, 72Mhz, 256KB Flash, 64KB RAM ', 'farnell': u'1624136', 'name': u'STM32F103RC', 'datasheet': '', 'quantity': 4, 'category_id': u'Microcontroller', 'number': 135, 'id': 113}, 136: {'category': u'Microcontroller', 'comment': u'Cortex M4, 84Mhz, 128KB Flash, 128KB RAM ', 'farnell': u'2393643', 'name': u'STM32F401RB', 'datasheet': '', 'quantity': 5, 'category_id': u'Microcontroller', 'number': 136, 'id': 141}, 137: {'category': u'Microcontroller', 'comment': u'Cortex M4, 84Mhz, 256KB Flash, 128KB RAM ', 'farnell': u'2393644', 'name': u'STM32F401RC', 'datasheet': '', 'quantity': 2, 'category_id': u'Microcontroller', 'number': 137, 'id': 140}, 138: {'category': u'Microcontroller', 'comment': u'Cortex M4, 84Mhz, 512KB Flash, 128KB RAM ', 'farnell': u'2432108', 'name': u'STM32F401RE', 'datasheet': '', 'quantity': 3, 'category_id': u'Microcontroller', 'number': 138, 'id': 139}, 139: {'category': u'Microcontroller', 'comment': u'Cortex M4, 100Mhz, 256KB Flash, 128KB RAM ', 'farnell': u'2456966', 'name': u'STM32F411RC', 'datasheet': '', 'quantity': 5, 'category_id': u'Microcontroller', 'number': 139, 'id': 149}, 140: {'category': u'Microcontroller', 'comment': u'Cortex M4, 100Mhz, 512KB Flash, 128KB RAM ', 'farnell': '', 'name': u'STM32F411RE', 'datasheet': u'http://www.st.com/st-web-ui/static/active/en/resource/technical/document/datasheet/DM00115249.pdf', 'quantity': 2, 'category_id': u'Microcontroller', 'number': 140, 'id': 132}, 141: {'category': u'Microcontroller', 'comment': u'Cortex M4, 180Mhz, 1MB Flash, 256KB RAM ', 'farnell': u'2333370', 'name': u'STM32F427ZG', 'datasheet': '', 'quantity': 5, 'category_id': u'Microcontroller', 'number': 141, 'id': 138}, 142: {'category': u'Diode', 'comment': u'40 V, 2 A, Low drop power Schottky rectifier', 'farnell': u'1373662', 'name': u'STPS2L40U', 'datasheet': u'http://www.st.com/web/en/resource/technical/document/datasheet/CD00002299.pdf', 'quantity': 20, 'category_id': u'Diode', 'number': 142, 'id': 39}, 143: {'category': u'Capacitor', 'comment': u'low esr 330uF Tantal', 'farnell': u'2395823', 'name': u'T520D337M006', 'datasheet': u'http://www.st.com/st-web-ui/static/active/en/resource/technical/document/datasheet/CD00191185.pdf', 'quantity': 13, 'category_id': u'Capacitor', 'number': 143, 'id': 114}, 144: {'category': u'LDO', 'comment': u'1.8 V, 100 mA CMOS LDOs with Shutdown and Reference Bypass', 'farnell': u'9762523', 'name': u'TC1015-1.8V', 'datasheet': u'http://ww1.microchip.com/downloads/en/DeviceDoc/21335e.pdf', 'quantity': 0, 'category_id': u'LDO', 'number': 144, 'id': 167}, 145: {'category': u'LDO', 'comment': u'2.8 V, 100 mA CMOS LDOs with Shutdown and Reference Bypass', 'farnell': u'1212684', 'name': u'TC1015-2.8V', 'datasheet': u'http://ww1.microchip.com/downloads/en/DeviceDoc/21335e.pdf', 'quantity': 20, 'category_id': u'LDO', 'number': 145, 'id': 21}, 146: {'category': u'LDO', 'comment': u'3.3 V, 100 mA CMOS LDOs with Shutdown and Reference Bypass', 'farnell': u'1331505', 'name': u'TC1015-3.3V', 'datasheet': u'http://ww1.microchip.com/downloads/en/DeviceDoc/21335e.pdf', 'quantity': 0, 'category_id': u'LDO', 'number': 146, 'id': 20}, 147: {'category': u'Frontend', 'comment': u'Ultrasonic Sensing Analog Front End (AFE) for Level Sensing, Flow Sensing, Concentration Sensing, and Proximity Sensing Applications', 'farnell': '', 'name': u'TDC1000PWR', 'datasheet': u'http://www.ti.com/lit/ds/symlink/tdc1000-q1.pdf', 'quantity': 4, 'category_id': u'Frontend', 'number': 147, 'id': 162}, 148: {'category': u'IC', 'comment': u'Time-to-Digital Converter for Time-of-Flight Applications in LIDAR, Magnetostrictive and Flow Meters', 'farnell': '', 'name': u'TDC7200PWR', 'datasheet': u'http://www.ti.com/lit/ds/symlink/tdc7200.pdf', 'quantity': 4, 'category_id': u'IC', 'number': 148, 'id': 161}, 149: {'category': u'LDO', 'comment': u'3.3 V, 1.5-A Low-Noise Fast-Transient-Response Low-Dropout Regulator', 'farnell': '', 'name': u'TL1963A-33K', 'datasheet': u'http://www.ti.com/lit/ds/symlink/tl1963a-18.pdf', 'quantity': 8, 'category_id': u'LDO', 'number': 149, 'id': 64}, 150: {'category': u'IC', 'comment': u'Low-Power 16-Channel Constant-Current LED Sink Driver\n', 'farnell': u'1694438', 'name': u'TLC5925', 'datasheet': u'http://www.ti.com/lit/ds/symlink/tlc5925.pdf', 'quantity': 2, 'category_id': u'IC', 'number': 150, 'id': 95}, 151: {'category': u'Connector', 'comment': u'reseptacle 2mm dual smd pin hader 16Way', 'farnell': u'1668474', 'name': u'TLE-108-01-G-DV', 'datasheet': u'http://www.farnell.com/datasheets/1801429.pdf', 'quantity': 7, 'category_id': u'Connector', 'number': 151, 'id': 164}, 152: {'category': u'Trafo/Isolator', 'comment': u'optocoupler', 'farnell': '', 'name': u'TLP2362', 'datasheet': '', 'quantity': 5, 'category_id': u'Trafo/Isolator', 'number': 152, 'id': 48}, 153: {'category': u'LDO', 'comment': u'3.3 V Adjustable and Fixed Low-Dropout Voltage Regulator', 'farnell': '', 'name': u'TLV1117-33', 'datasheet': u'http://www.ti.com/lit/ds/symlink/tlv1117-18.pdf', 'quantity': 3, 'category_id': u'LDO', 'number': 153, 'id': 65}, 154: {'category': u'LDO', 'comment': u'5 V Adjustable and Fixed Low-Dropout Voltage Regulator', 'farnell': '', 'name': u'TLV1117-50', 'datasheet': u'http://www.ti.com/lit/ds/symlink/tlv1117-18.pdf', 'quantity': 4, 'category_id': u'LDO', 'number': 154, 'id': 67}, 155: {'category': u'Boost Switcher', 'comment': u'Low-Input Voltage Step-Up Converter in Thin SOT-23 Package\n', 'farnell': u'2323546', 'name': u'TLV61220', 'datasheet': u'http://www.ti.com/lit/ds/symlink/tlv61220.pdf', 'quantity': 4, 'category_id': u'Boost Switcher', 'number': 155, 'id': 151}, 156: {'category': u'OP AMP', 'comment': u'1.4-W MONO Filter-Free Class-D Audio Power Amplifier\n', 'farnell': u'2075395', 'name': u'TPA2005', 'datasheet': u'http://www.ti.com/lit/ds/symlink/tpa2005d1.pdf', 'quantity': 3, 'category_id': u'OP AMP', 'number': 156, 'id': 54}, 157: {'category': u'Buck Switcher', 'comment': u'4.5V to 17V Input, 5A Synchronous Step Down Converter\n', 'farnell': '', 'name': u'TPS54521RHLR', 'datasheet': u'http://www.ti.com/lit/ds/symlink/tps54521.pdf', 'quantity': 9, 'category_id': u'Buck Switcher', 'number': 157, 'id': 148}, 158: {'category': u'Buck Switcher', 'comment': u'4.5-V To 18-V Input, 5-A Synchronous Step-Down Converter With Eco-Mode\u2122\n', 'farnell': u'2064230', 'name': u'TPS54528DDA', 'datasheet': u'http://www.ti.com/lit/ds/symlink/tps54528.pdf', 'quantity': 7, 'category_id': u'Buck Switcher', 'number': 158, 'id': 105}, 159: {'category': u'LDO', 'comment': u'Single Output LDO, 150mA, Fixed (1.8V), High PSRR, Low Quiescent Current, Low Noise', 'farnell': u'1329606', 'name': u'TPS71718', 'datasheet': u'http://www.ti.com/lit/ds/symlink/tps717.pdf', 'quantity': 5, 'category_id': u'LDO', 'number': 159, 'id': 153}, 160: {'category': u'LDO', 'comment': u'Single Output LDO, 150mA, Fixed (3.0V), High PSRR, Low Quiescent Current, Low Noise', 'farnell': u'1755491', 'name': u'TPS71733', 'datasheet': u'http://www.ti.com/lit/ds/symlink/tps717.pdf', 'quantity': 5, 'category_id': u'LDO', 'number': 160, 'id': 152}, 161: {'category': u'OP AMP', 'comment': u'Rail-to-rail input/output 29 \xb5A 420 kHz CMOS operational amplifiers', 'farnell': '', 'name': u'TSV621', 'datasheet': u'http://www.st.com/web/en/resource/technical/document/datasheet/CD00205073.pdf', 'quantity': 5, 'category_id': u'OP AMP', 'number': 161, 'id': 82}, 162: {'category': u'OP AMP', 'comment': u'Rail-to-rail input/output 20 MHz GBP operational amplifiers', 'farnell': u'1842599', 'name': u'TSV992IST', 'datasheet': u'http://www.st.com/web/en/resource/technical/document/datasheet/CD00144611.pdf', 'quantity': 2, 'category_id': u'OP AMP', 'number': 162, 'id': 81}, 163: {'category': u'OP AMP', 'comment': u'Rail-to-rail input/output 20 MHz GBP operational amplifiers', 'farnell': u'1842597', 'name': u'TSV994AIYPT', 'datasheet': u'http://www.st.com/web/en/resource/technical/document/datasheet/CD00144611.pdf', 'quantity': 5, 'category_id': u'OP AMP', 'number': 163, 'id': 80}, 164: {'category': u'Oscillator/Crystal', 'comment': u'8.192MHz 10ppm', 'farnell': u'1842271', 'name': u'TXC \t9B-8.192MEEJ-B', 'datasheet': u'http://www.farnell.com/datasheets/1497894.pdf', 'quantity': 1, 'category_id': u'Oscillator/Crystal', 'number': 164, 'id': 98}, 165: {'category': u'Inductor/Ferrite', 'comment': u'10 \xb5H, \xb1 20%, Shielded, 0.18 ohm, 840 mA', 'farnell': u'2292521', 'name': u'YS4018100M-10', 'datasheet': u'http://www.farnell.com/datasheets/1685912.pdf', 'quantity': 3, 'category_id': u'Inductor/Ferrite', 'number': 165, 'id': 102}}
			for i in range(len(self.BaseElements)):
				self.BaseElement_name.append(self.BaseElements[i]['name'])
		else:
			for i in range(len(self.parent.elementBaseThread.elementBase.elementDictionary)):
				self.BaseElement_name.append(self.parent.elementBaseThread.elementBase.elementDictionary[i]['name'].upper())

		#print self.parent.elementBaseThread.elementBase.elementDictionary

		self.create_main_panel()

		self.Show(True)


	def create_main_panel(self):
		self.panel = wx.Panel(self,wx.ID_ANY)
		self.SetBackgroundColour(wx.WHITE)
		self.font = wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_NORMAL)
		self.fontB = wx.Font(12, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_BOLD)
		self.fontS = wx.Font(10, family = wx.FONTFAMILY_DEFAULT, style = wx.FONTSTYLE_NORMAL, weight = wx.FONTWEIGHT_NORMAL)

		logo = wx.Bitmap(LOGONAME)
		self.logoIcon  = wx.StaticBitmap(self.panel, wx.ID_ANY, logo)
		
		self.laodBOM_button = wx.Button(self.panel, label="Load BOM file",size=(150,30))
		self.laodBOM_button.Bind(wx.EVT_BUTTON, self.onLoadBom)
		self.laodBOM_button.SetFont(self.font)

		self.add_button = wx.Button(self.panel, label="Add to Base",size=(150,30))
		self.add_button.Bind(wx.EVT_BUTTON, self.onAdd)
		self.add_button.SetFont(self.font)
		self.add_button.Hide()

		self.name_st = wx.StaticText(self.panel, label="Name: ", size=(100,25))
		self.name_st.SetFont(self.font)
		self.name_tc = wx.TextCtrl(self.panel, size=(200,25), style=wx.TE_RICH2)
		self.name_tc.SetFont(self.font)
		self.name_box = wx.BoxSizer(wx.HORIZONTAL)
		self.name_box.Add(self.name_st)
		self.name_box.Add(self.name_tc,1,wx.EXPAND)


		self.description_st = wx.StaticText(self.panel, label="Description: ", size=(100,25))
		self.description_st.SetFont(self.font)
		self.description_tc = wx.TextCtrl(self.panel, size=(200,25), style=wx.TE_RICH2)
		self.description_tc.SetFont(self.font)
		self.description_box = wx.BoxSizer(wx.HORIZONTAL)
		self.description_box.Add(self.description_st)
		self.description_box.Add(self.description_tc,1,wx.EXPAND)

		self.elements_vbox = wx.BoxSizer(wx.VERTICAL)
		self.scrolled_panel = wx.lib.scrolledpanel.ScrolledPanel(self.panel, -1, style = wx.TAB_TRAVERSAL | wx.SUNKEN_BORDER, name="scrollpanel")
		self.scrolled_panel.SetAutoLayout(1)
		self.scrolled_panel.SetupScrolling()
		self.scrolled_panel.Hide()

		self.scrolled_panel.SetSizerAndFit(self.elements_vbox)

		self.vbox = wx.BoxSizer(wx.VERTICAL)
		self.vbox.Add(self.logoIcon,0,wx.CENTER)
		self.vbox.AddSpacer(20)
		self.vbox.Add(self.name_box,0,wx.CENTER)
		self.vbox.AddSpacer(5)
		self.vbox.Add(self.description_box,0,wx.CENTER)
		self.vbox.AddSpacer(5)
		self.vbox.Add(self.laodBOM_button,0,wx.CENTER)
		self.vbox.AddSpacer(10)
		self.vbox.Add(self.scrolled_panel,1,wx.EXPAND)
		self.vbox.AddSpacer(10)
		self.vbox.Add(self.add_button,0,wx.CENTER)
		self.vbox.AddSpacer(10)

		#definiranje vseh saizerjev
		self.mainSizer = wx.BoxSizer(wx.HORIZONTAL)

		#celotna oblika
		self.mainSizer.AddSpacer(30)
		self.mainSizer.Add(self.vbox,1,wx.EXPAND)
		self.mainSizer.AddSpacer(30)

		self.panel.SetSizerAndFit(self.mainSizer)
		self.mainSizer.Fit(self)

	def recreate_main_panel(self):
		for i in range(len(self.hboxs)):
			self.elements_vbox.Show(self.hboxs[i], show = False, recursive = True)
			self.elements_vbox.Detach(self.hboxs[i])

		self.wx_elements =[]
		self.hboxs = []
		self.choice_elements = []

		for i in range(len(self.elements)):
			self.wx_elements.append([])
			self.hboxs.append(wx.BoxSizer(wx.HORIZONTAL))
			self.choice_elements.append([])

			self.wx_elements[-1].append(wx.StaticText(self.scrolled_panel, label=self.elements[i]['name'], size=(150,20)))
			self.wx_elements[-1].append(wx.StaticText(self.scrolled_panel, label=str(self.elements[i]['quantity']), size=(50,20)))


			choices = []
			if self.elements[i]['name'].upper() in self.BaseElement_name:
				index = self.BaseElement_name.index(self.elements[i]['name'])
				choices.append(self.BaseElement_name[index])
				self.choice_elements[-1].append(self.parent.elementBaseThread.elementBase.elementDictionary[index])


			for m in range(10):
				if m < (len(self.elements[i]['name'])/2):
					for k in range(len(self.BaseElement_name)):
						if self.elements[i]['name'].upper()[:-m] == self.BaseElement_name[k][:len(self.elements[i]['name'])-m]:
							if self.BaseElement_name[k] not in choices:
								choices.append(self.BaseElement_name[k])
								self.choice_elements[-1].append(self.parent.elementBaseThread.elementBase.elementDictionary[k])
				else:
					break


			if len(choices) == 0:
				choices.append("")
				choices.extend(self.BaseElement_name)

				self.choice_elements[-1].append("")
				for j in range(len(self.parent.elementBaseThread.elementBase.elementDictionary)):
					self.choice_elements[-1].append(self.parent.elementBaseThread.elementBase.elementDictionary[j])
			else:
				choices.append("")

			#print self.choice_elements[-1]

			self.wx_elements[-1].append(wx.Choice(self.scrolled_panel, choices=choices, size=(180,20)))
			self.wx_elements[-1][-1].SetSelection(0)

			for j in range(len(self.wx_elements[-1])):
				self.hboxs[-1].Add(self.wx_elements[-1][j])
				self.hboxs[-1].AddSpacer(5)


			self.elements_vbox.Add(self.hboxs[-1])
			#self.vbox.AddSpacer(1)

		self.scrolled_panel.Show()
		self.add_button.Show()

		self.scrolled_panel.SetSizerAndFit(self.elements_vbox)
		self.scrolled_panel.Layout()
		self.scrolled_panel.SetupScrolling()

		self.panel.SetSizerAndFit(self.mainSizer)
		self.mainSizer.Fit(self)


	def onLoadBom(self, event):
		self.openBOMFile()

	def readBOMfile(self,path):
		self.bomFile = open(path,'r')
		bomLines = self.bomFile.readlines()[2:]

		self.elements = []

		self.capacitors = []
		self.resistors = []

		for i in range(len(bomLines)):
			
			line = bomLines[i].strip("\r\n").split('"')

			while '\t' in line: line.remove('\t');


			if line[1].upper() == "CAP":
				caps_values = line[6].split(" ")
				caps_disignators = line[3].split(" ")
				for j in range(len(caps_values)):
					cap_found = False
					for k in range(len(self.capacitors)):
						if line[4] == self.capacitors[k]['case'] and caps_values[j].strip(',') == self.capacitors[k]['value']:
							cap_found = True
							self.capacitors[k]['quantity'] += 1

					if cap_found == False:
						self.capacitors.append({'name':line[1].upper() + " " + line[4] + " " + caps_values[j].strip(','), 'description':line[2], 'case':line[4], 'value':caps_values[j].strip(','), 'disignator':caps_disignators[j],'quantity':1})

			elif line[1].upper() == "RES":
				ress_values = line[6].split(" ")
				ress_disignators = line[3].split(" ")
				for j in range(len(ress_values)):
					cap_found = False
					for k in range(len(self.resistors)):
						if line[4] == self.resistors[k]['case'] and ress_values[j].strip(',') == self.resistors[k]['value']:
							cap_found = True
							self.resistors[k]['quantity'] += 1

					if cap_found == False:
						self.resistors.append({'name':line[1].upper() + " " + line[4] + " " + ress_values[j].strip(','), 'description':line[2], 'case':line[4], 'value':ress_values[j].strip(','), 'disignator':ress_disignators[j],'quantity':1})

			else:
				element = {'name':line[1].upper(), 'description':line[2],'disignator':line[3],'quantity':int(line[5])}
				self.elements.append(element)


			#print line
			#print element

		for i in range(len(self.capacitors)):
			self.elements.append(self.capacitors[i])
		for i in range(len(self.resistors)):
			self.elements.append(self.resistors[i])

		self.recreate_main_panel()

		#print bomlines

	def openBOMFile(self):
		self.currentDirectory = self.currentDirectory +"\\"
		wildcard = "BOM txt source (*.txt)|*.txt|" \
            "All files (*.*)|*.*"
        # Do dialog stuff. Don't add wx.FD_CHANGE_DIR |  to the style
		dlg = wx.FileDialog(
			self, message="Choose a file",
			defaultDir=self.currentDirectory, 
			defaultFile="",
			wildcard=wildcard,
			style=wx.FD_OPEN  | wx.FD_PREVIEW | wx.FD_FILE_MUST_EXIST)
		if dlg.ShowModal() == wx.ID_OK:
			paths = dlg.GetPaths()
			#print "You chose the following file(s):"
			for path in paths:
				print path
				self.readBOMfile(path)

				
		dlg.Destroy()

	def onAdd(self, event):
		name = self.name_tc.GetLineText(0)
		description = self.description_tc.GetLineText(0)

		elements = {}

		for i in range(len(self.wx_elements)):
			selected_element = self.wx_elements[i][2].GetSelection()
			selected_element_str = self.wx_elements[i][2].GetString(selected_element)
			selected_element_quantity = int(self.wx_elements[i][1].GetLabel())
			if selected_element_str:
				#print self.wx_elements[i][0].GetLabel(), selected_element_str, self.choice_elements[i][selected_element]
				elements[str(self.choice_elements[i][selected_element]['id'])] = int(selected_element_quantity)


		#print name, description
		#print elements
		self.parent.elementBaseThread.AddProdcustList.append([name,description,elements])
		self.Destroy()




if __name__ == '__main__':
	app = wx.App(False)
	app.frame = AltiumToBaseFrame(None, "Altium to Base")
	app.frame.Show()
	app.SetTopWindow(app.frame)

	app.MainLoop()

