import TopBar from './TopBar';
import ServiceTabs from './ServiceTabs';
import Nav from './Nav';
import StickySearch from './StickySearch';

export default function Header() {
  return (
    <>
      <TopBar />
      <ServiceTabs />
      <Nav />
      <StickySearch />
    </>
  );
}
