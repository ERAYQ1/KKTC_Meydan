import app from 'flarum/forum/app';
import Link from 'flarum/common/components/Link';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import icon from 'flarum/common/helpers/icon';

export default function discoverSheetBody(onNavigate) {
  const allTags = app.store.all('tags');

  if (!allTags.length) {
    return <LoadingIndicator />;
  }

  const tags = allTags.filter((tag) => !tag.isChild() && tag.position() !== null);

  tags.sort((a, b) => a.position() - b.position());

  return (
    <ul className="MobileDiscoverList">
      {tags.map((tag) => (
        <li>
          <Link href={app.route.tag(tag)} onclick={() => onNavigate()}>
            {tag.icon() && <span className="MobileDiscoverList-icon">{icon(tag.icon())}</span>}
            <span className="MobileDiscoverList-name" style={{ color: tag.color() }}>
              {tag.name()}
            </span>
          </Link>
        </li>
      ))}
    </ul>
  );
}
