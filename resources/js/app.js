import { runPageInitializers } from './support/page';
import './navigation';
import './drawer';
import './collapsible';
import './privacy';
import './theme';
import './tasks';
import './payroll';
import './month-picker';
import './tools/clipboard';
import './tools/json-formatter';
import './tools/timestamp';

// Page scripts register themselves with onPageLoad(); run them for the first page.
// navigation.js runs them again after every soft navigation.
runPageInitializers();
