import logging
import logging.handlers


class LogHandler(object) :
    def __init__(self, stream=None) :
        self.lines = []
        self._line = ''

    def write(self, value) :
        self.line = value

    @property
    def line(self) :
        return self._line

    @line.setter
    def line(self, value) :
        self._line = value
        self.lines.append(value)

    @line.deleter
    def line(self) :
        del self._line

