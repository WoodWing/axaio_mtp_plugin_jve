/**
 * Advert.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pln;

public class Advert  implements java.io.Serializable {
    private java.lang.String id;

    private java.lang.String alienId;

    private java.lang.String publication;

    private java.lang.String issue;

    private java.lang.String pubChannel;

    private java.lang.String section;

    private java.lang.String status;

    private java.lang.String name;

    private java.lang.String adType;

    private java.lang.String comment;

    private java.lang.String source;

    private java.lang.String colorSpace;

    private java.lang.String description;

    private java.lang.String plainContent;

    private com.woodwing.enterprise.interfaces.services.pln.Attachment file;

    private java.lang.String highResFile;

    private org.apache.axis.types.UnsignedInt pageOrder;

    private com.woodwing.enterprise.interfaces.services.pln.Page page;

    private com.woodwing.enterprise.interfaces.services.pln.Placement placement;

    private java.lang.String preferredPlacement;

    private com.woodwing.enterprise.interfaces.services.pln.PublishPrioType publishPrio;

    private java.lang.Double rate;

    private com.woodwing.enterprise.interfaces.services.pln.Edition[] editions;

    private java.util.Calendar deadline;

    private org.apache.axis.types.UnsignedInt pageSequence;

    private java.lang.String version;

    public Advert() {
    }

    public Advert(
           java.lang.String id,
           java.lang.String alienId,
           java.lang.String publication,
           java.lang.String issue,
           java.lang.String pubChannel,
           java.lang.String section,
           java.lang.String status,
           java.lang.String name,
           java.lang.String adType,
           java.lang.String comment,
           java.lang.String source,
           java.lang.String colorSpace,
           java.lang.String description,
           java.lang.String plainContent,
           com.woodwing.enterprise.interfaces.services.pln.Attachment file,
           java.lang.String highResFile,
           org.apache.axis.types.UnsignedInt pageOrder,
           com.woodwing.enterprise.interfaces.services.pln.Page page,
           com.woodwing.enterprise.interfaces.services.pln.Placement placement,
           java.lang.String preferredPlacement,
           com.woodwing.enterprise.interfaces.services.pln.PublishPrioType publishPrio,
           java.lang.Double rate,
           com.woodwing.enterprise.interfaces.services.pln.Edition[] editions,
           java.util.Calendar deadline,
           org.apache.axis.types.UnsignedInt pageSequence,
           java.lang.String version) {
           this.id = id;
           this.alienId = alienId;
           this.publication = publication;
           this.issue = issue;
           this.pubChannel = pubChannel;
           this.section = section;
           this.status = status;
           this.name = name;
           this.adType = adType;
           this.comment = comment;
           this.source = source;
           this.colorSpace = colorSpace;
           this.description = description;
           this.plainContent = plainContent;
           this.file = file;
           this.highResFile = highResFile;
           this.pageOrder = pageOrder;
           this.page = page;
           this.placement = placement;
           this.preferredPlacement = preferredPlacement;
           this.publishPrio = publishPrio;
           this.rate = rate;
           this.editions = editions;
           this.deadline = deadline;
           this.pageSequence = pageSequence;
           this.version = version;
    }


    /**
     * Gets the id value for this Advert.
     * 
     * @return id
     */
    public java.lang.String getId() {
        return id;
    }


    /**
     * Sets the id value for this Advert.
     * 
     * @param id
     */
    public void setId(java.lang.String id) {
        this.id = id;
    }


    /**
     * Gets the alienId value for this Advert.
     * 
     * @return alienId
     */
    public java.lang.String getAlienId() {
        return alienId;
    }


    /**
     * Sets the alienId value for this Advert.
     * 
     * @param alienId
     */
    public void setAlienId(java.lang.String alienId) {
        this.alienId = alienId;
    }


    /**
     * Gets the publication value for this Advert.
     * 
     * @return publication
     */
    public java.lang.String getPublication() {
        return publication;
    }


    /**
     * Sets the publication value for this Advert.
     * 
     * @param publication
     */
    public void setPublication(java.lang.String publication) {
        this.publication = publication;
    }


    /**
     * Gets the issue value for this Advert.
     * 
     * @return issue
     */
    public java.lang.String getIssue() {
        return issue;
    }


    /**
     * Sets the issue value for this Advert.
     * 
     * @param issue
     */
    public void setIssue(java.lang.String issue) {
        this.issue = issue;
    }


    /**
     * Gets the pubChannel value for this Advert.
     * 
     * @return pubChannel
     */
    public java.lang.String getPubChannel() {
        return pubChannel;
    }


    /**
     * Sets the pubChannel value for this Advert.
     * 
     * @param pubChannel
     */
    public void setPubChannel(java.lang.String pubChannel) {
        this.pubChannel = pubChannel;
    }


    /**
     * Gets the section value for this Advert.
     * 
     * @return section
     */
    public java.lang.String getSection() {
        return section;
    }


    /**
     * Sets the section value for this Advert.
     * 
     * @param section
     */
    public void setSection(java.lang.String section) {
        this.section = section;
    }


    /**
     * Gets the status value for this Advert.
     * 
     * @return status
     */
    public java.lang.String getStatus() {
        return status;
    }


    /**
     * Sets the status value for this Advert.
     * 
     * @param status
     */
    public void setStatus(java.lang.String status) {
        this.status = status;
    }


    /**
     * Gets the name value for this Advert.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this Advert.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the adType value for this Advert.
     * 
     * @return adType
     */
    public java.lang.String getAdType() {
        return adType;
    }


    /**
     * Sets the adType value for this Advert.
     * 
     * @param adType
     */
    public void setAdType(java.lang.String adType) {
        this.adType = adType;
    }


    /**
     * Gets the comment value for this Advert.
     * 
     * @return comment
     */
    public java.lang.String getComment() {
        return comment;
    }


    /**
     * Sets the comment value for this Advert.
     * 
     * @param comment
     */
    public void setComment(java.lang.String comment) {
        this.comment = comment;
    }


    /**
     * Gets the source value for this Advert.
     * 
     * @return source
     */
    public java.lang.String getSource() {
        return source;
    }


    /**
     * Sets the source value for this Advert.
     * 
     * @param source
     */
    public void setSource(java.lang.String source) {
        this.source = source;
    }


    /**
     * Gets the colorSpace value for this Advert.
     * 
     * @return colorSpace
     */
    public java.lang.String getColorSpace() {
        return colorSpace;
    }


    /**
     * Sets the colorSpace value for this Advert.
     * 
     * @param colorSpace
     */
    public void setColorSpace(java.lang.String colorSpace) {
        this.colorSpace = colorSpace;
    }


    /**
     * Gets the description value for this Advert.
     * 
     * @return description
     */
    public java.lang.String getDescription() {
        return description;
    }


    /**
     * Sets the description value for this Advert.
     * 
     * @param description
     */
    public void setDescription(java.lang.String description) {
        this.description = description;
    }


    /**
     * Gets the plainContent value for this Advert.
     * 
     * @return plainContent
     */
    public java.lang.String getPlainContent() {
        return plainContent;
    }


    /**
     * Sets the plainContent value for this Advert.
     * 
     * @param plainContent
     */
    public void setPlainContent(java.lang.String plainContent) {
        this.plainContent = plainContent;
    }


    /**
     * Gets the file value for this Advert.
     * 
     * @return file
     */
    public com.woodwing.enterprise.interfaces.services.pln.Attachment getFile() {
        return file;
    }


    /**
     * Sets the file value for this Advert.
     * 
     * @param file
     */
    public void setFile(com.woodwing.enterprise.interfaces.services.pln.Attachment file) {
        this.file = file;
    }


    /**
     * Gets the highResFile value for this Advert.
     * 
     * @return highResFile
     */
    public java.lang.String getHighResFile() {
        return highResFile;
    }


    /**
     * Sets the highResFile value for this Advert.
     * 
     * @param highResFile
     */
    public void setHighResFile(java.lang.String highResFile) {
        this.highResFile = highResFile;
    }


    /**
     * Gets the pageOrder value for this Advert.
     * 
     * @return pageOrder
     */
    public org.apache.axis.types.UnsignedInt getPageOrder() {
        return pageOrder;
    }


    /**
     * Sets the pageOrder value for this Advert.
     * 
     * @param pageOrder
     */
    public void setPageOrder(org.apache.axis.types.UnsignedInt pageOrder) {
        this.pageOrder = pageOrder;
    }


    /**
     * Gets the page value for this Advert.
     * 
     * @return page
     */
    public com.woodwing.enterprise.interfaces.services.pln.Page getPage() {
        return page;
    }


    /**
     * Sets the page value for this Advert.
     * 
     * @param page
     */
    public void setPage(com.woodwing.enterprise.interfaces.services.pln.Page page) {
        this.page = page;
    }


    /**
     * Gets the placement value for this Advert.
     * 
     * @return placement
     */
    public com.woodwing.enterprise.interfaces.services.pln.Placement getPlacement() {
        return placement;
    }


    /**
     * Sets the placement value for this Advert.
     * 
     * @param placement
     */
    public void setPlacement(com.woodwing.enterprise.interfaces.services.pln.Placement placement) {
        this.placement = placement;
    }


    /**
     * Gets the preferredPlacement value for this Advert.
     * 
     * @return preferredPlacement
     */
    public java.lang.String getPreferredPlacement() {
        return preferredPlacement;
    }


    /**
     * Sets the preferredPlacement value for this Advert.
     * 
     * @param preferredPlacement
     */
    public void setPreferredPlacement(java.lang.String preferredPlacement) {
        this.preferredPlacement = preferredPlacement;
    }


    /**
     * Gets the publishPrio value for this Advert.
     * 
     * @return publishPrio
     */
    public com.woodwing.enterprise.interfaces.services.pln.PublishPrioType getPublishPrio() {
        return publishPrio;
    }


    /**
     * Sets the publishPrio value for this Advert.
     * 
     * @param publishPrio
     */
    public void setPublishPrio(com.woodwing.enterprise.interfaces.services.pln.PublishPrioType publishPrio) {
        this.publishPrio = publishPrio;
    }


    /**
     * Gets the rate value for this Advert.
     * 
     * @return rate
     */
    public java.lang.Double getRate() {
        return rate;
    }


    /**
     * Sets the rate value for this Advert.
     * 
     * @param rate
     */
    public void setRate(java.lang.Double rate) {
        this.rate = rate;
    }


    /**
     * Gets the editions value for this Advert.
     * 
     * @return editions
     */
    public com.woodwing.enterprise.interfaces.services.pln.Edition[] getEditions() {
        return editions;
    }


    /**
     * Sets the editions value for this Advert.
     * 
     * @param editions
     */
    public void setEditions(com.woodwing.enterprise.interfaces.services.pln.Edition[] editions) {
        this.editions = editions;
    }


    /**
     * Gets the deadline value for this Advert.
     * 
     * @return deadline
     */
    public java.util.Calendar getDeadline() {
        return deadline;
    }


    /**
     * Sets the deadline value for this Advert.
     * 
     * @param deadline
     */
    public void setDeadline(java.util.Calendar deadline) {
        this.deadline = deadline;
    }


    /**
     * Gets the pageSequence value for this Advert.
     * 
     * @return pageSequence
     */
    public org.apache.axis.types.UnsignedInt getPageSequence() {
        return pageSequence;
    }


    /**
     * Sets the pageSequence value for this Advert.
     * 
     * @param pageSequence
     */
    public void setPageSequence(org.apache.axis.types.UnsignedInt pageSequence) {
        this.pageSequence = pageSequence;
    }


    /**
     * Gets the version value for this Advert.
     * 
     * @return version
     */
    public java.lang.String getVersion() {
        return version;
    }


    /**
     * Sets the version value for this Advert.
     * 
     * @param version
     */
    public void setVersion(java.lang.String version) {
        this.version = version;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Advert)) return false;
        Advert other = (Advert) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.id==null && other.getId()==null) || 
             (this.id!=null &&
              this.id.equals(other.getId()))) &&
            ((this.alienId==null && other.getAlienId()==null) || 
             (this.alienId!=null &&
              this.alienId.equals(other.getAlienId()))) &&
            ((this.publication==null && other.getPublication()==null) || 
             (this.publication!=null &&
              this.publication.equals(other.getPublication()))) &&
            ((this.issue==null && other.getIssue()==null) || 
             (this.issue!=null &&
              this.issue.equals(other.getIssue()))) &&
            ((this.pubChannel==null && other.getPubChannel()==null) || 
             (this.pubChannel!=null &&
              this.pubChannel.equals(other.getPubChannel()))) &&
            ((this.section==null && other.getSection()==null) || 
             (this.section!=null &&
              this.section.equals(other.getSection()))) &&
            ((this.status==null && other.getStatus()==null) || 
             (this.status!=null &&
              this.status.equals(other.getStatus()))) &&
            ((this.name==null && other.getName()==null) || 
             (this.name!=null &&
              this.name.equals(other.getName()))) &&
            ((this.adType==null && other.getAdType()==null) || 
             (this.adType!=null &&
              this.adType.equals(other.getAdType()))) &&
            ((this.comment==null && other.getComment()==null) || 
             (this.comment!=null &&
              this.comment.equals(other.getComment()))) &&
            ((this.source==null && other.getSource()==null) || 
             (this.source!=null &&
              this.source.equals(other.getSource()))) &&
            ((this.colorSpace==null && other.getColorSpace()==null) || 
             (this.colorSpace!=null &&
              this.colorSpace.equals(other.getColorSpace()))) &&
            ((this.description==null && other.getDescription()==null) || 
             (this.description!=null &&
              this.description.equals(other.getDescription()))) &&
            ((this.plainContent==null && other.getPlainContent()==null) || 
             (this.plainContent!=null &&
              this.plainContent.equals(other.getPlainContent()))) &&
            ((this.file==null && other.getFile()==null) || 
             (this.file!=null &&
              this.file.equals(other.getFile()))) &&
            ((this.highResFile==null && other.getHighResFile()==null) || 
             (this.highResFile!=null &&
              this.highResFile.equals(other.getHighResFile()))) &&
            ((this.pageOrder==null && other.getPageOrder()==null) || 
             (this.pageOrder!=null &&
              this.pageOrder.equals(other.getPageOrder()))) &&
            ((this.page==null && other.getPage()==null) || 
             (this.page!=null &&
              this.page.equals(other.getPage()))) &&
            ((this.placement==null && other.getPlacement()==null) || 
             (this.placement!=null &&
              this.placement.equals(other.getPlacement()))) &&
            ((this.preferredPlacement==null && other.getPreferredPlacement()==null) || 
             (this.preferredPlacement!=null &&
              this.preferredPlacement.equals(other.getPreferredPlacement()))) &&
            ((this.publishPrio==null && other.getPublishPrio()==null) || 
             (this.publishPrio!=null &&
              this.publishPrio.equals(other.getPublishPrio()))) &&
            ((this.rate==null && other.getRate()==null) || 
             (this.rate!=null &&
              this.rate.equals(other.getRate()))) &&
            ((this.editions==null && other.getEditions()==null) || 
             (this.editions!=null &&
              java.util.Arrays.equals(this.editions, other.getEditions()))) &&
            ((this.deadline==null && other.getDeadline()==null) || 
             (this.deadline!=null &&
              this.deadline.equals(other.getDeadline()))) &&
            ((this.pageSequence==null && other.getPageSequence()==null) || 
             (this.pageSequence!=null &&
              this.pageSequence.equals(other.getPageSequence()))) &&
            ((this.version==null && other.getVersion()==null) || 
             (this.version!=null &&
              this.version.equals(other.getVersion())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getId() != null) {
            _hashCode += getId().hashCode();
        }
        if (getAlienId() != null) {
            _hashCode += getAlienId().hashCode();
        }
        if (getPublication() != null) {
            _hashCode += getPublication().hashCode();
        }
        if (getIssue() != null) {
            _hashCode += getIssue().hashCode();
        }
        if (getPubChannel() != null) {
            _hashCode += getPubChannel().hashCode();
        }
        if (getSection() != null) {
            _hashCode += getSection().hashCode();
        }
        if (getStatus() != null) {
            _hashCode += getStatus().hashCode();
        }
        if (getName() != null) {
            _hashCode += getName().hashCode();
        }
        if (getAdType() != null) {
            _hashCode += getAdType().hashCode();
        }
        if (getComment() != null) {
            _hashCode += getComment().hashCode();
        }
        if (getSource() != null) {
            _hashCode += getSource().hashCode();
        }
        if (getColorSpace() != null) {
            _hashCode += getColorSpace().hashCode();
        }
        if (getDescription() != null) {
            _hashCode += getDescription().hashCode();
        }
        if (getPlainContent() != null) {
            _hashCode += getPlainContent().hashCode();
        }
        if (getFile() != null) {
            _hashCode += getFile().hashCode();
        }
        if (getHighResFile() != null) {
            _hashCode += getHighResFile().hashCode();
        }
        if (getPageOrder() != null) {
            _hashCode += getPageOrder().hashCode();
        }
        if (getPage() != null) {
            _hashCode += getPage().hashCode();
        }
        if (getPlacement() != null) {
            _hashCode += getPlacement().hashCode();
        }
        if (getPreferredPlacement() != null) {
            _hashCode += getPreferredPlacement().hashCode();
        }
        if (getPublishPrio() != null) {
            _hashCode += getPublishPrio().hashCode();
        }
        if (getRate() != null) {
            _hashCode += getRate().hashCode();
        }
        if (getEditions() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getEditions());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getEditions(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getDeadline() != null) {
            _hashCode += getDeadline().hashCode();
        }
        if (getPageSequence() != null) {
            _hashCode += getPageSequence().hashCode();
        }
        if (getVersion() != null) {
            _hashCode += getVersion().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Advert.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Advert"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("id");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Id"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("alienId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "AlienId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publication");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Publication"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("issue");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Issue"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pubChannel");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PubChannel"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("section");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Section"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("status");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Status"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("name");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Name"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("adType");
        elemField.setXmlName(new javax.xml.namespace.QName("", "AdType"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("comment");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Comment"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("source");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Source"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("colorSpace");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ColorSpace"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("description");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Description"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("plainContent");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PlainContent"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("file");
        elemField.setXmlName(new javax.xml.namespace.QName("", "File"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Attachment"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("highResFile");
        elemField.setXmlName(new javax.xml.namespace.QName("", "HighResFile"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageOrder");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageOrder"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("page");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Page"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Page"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("placement");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Placement"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Placement"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("preferredPlacement");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PreferredPlacement"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publishPrio");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublishPrio"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "PublishPrioType"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("rate");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Rate"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("editions");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Editions"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartEditorialPlan", "Edition"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Edition"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("deadline");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Deadline"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "dateTime"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageSequence");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageSequence"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("version");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Version"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
